<?php

declare(strict_types=1);

namespace Decision\Controller;

use Decision\Controller\FileBrowser\FileReader;
use Decision\Model\Enums\MeetingTypes;
use Decision\Service\AclService;
use Decision\Service\Decision as DecisionService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

use function end;
use function explode;
use function intval;
use function preg_match;
use function strlen;
use function substr;

/**
 * @method FlashMessenger flashMessenger()
 */

class DecisionController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly DecisionService $decisionService,
        private readonly FileReader $fileReader,
    ) {
    }

    /**
     * Index action, shows meetings.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel(
            [
                'meetings' => $this->decisionService->getMeetings(),
                'meetingType' => null,
            ],
        );
    }

    /**
     * Download meeting minutes.
     */
    public function minutesAction(): ViewModel|Stream
    {
        $type = MeetingTypes::from($this->params()->fromRoute('type'));
        $number = (int) $this->params()->fromRoute('number');

        $meeting = $this->decisionService->getMeeting($type, $number);
        if (null !== $meeting) {
            $response = $this->decisionService->getMeetingMinutesDownload($meeting);

            if (null !== $response) {
                return $response;
            }
        }

        return $this->notFoundAction();
    }

    public function documentAction(): ViewModel|Stream
    {
        $id = (int) $this->params()->fromRoute('id');
        $meetingDocument = $this->decisionService->getMeetingDocument($id);

        if (null !== $meetingDocument) {
            $response = $this->decisionService->getMeetingDocumentDownload($meetingDocument);

            if (null !== $response) {
                return $response;
            }
        }

        return $this->notFoundAction();
    }

    /**
     * View a meeting.
     */
    public function viewAction(): ViewModel
    {
        $type = MeetingTypes::from($this->params()->fromRoute('type'));
        $number = $this->params()->fromRoute('number');

        if (null === $number) {
            // view all meetings of certain type

            $view = new ViewModel(
                [
                    'meetings' => $this->decisionService->getMeetings(type: $type),
                    'meetingType' => $type,
                ],
            );

            $view->setTemplate('decision/index');

            return $view;
        }

        $meeting = $this->decisionService->getMeeting($type, intval($number));

        if (null === $meeting) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'meeting' => $meeting,
            ],
        );
    }

    /**
     * Search decisions.
     */
    public function searchAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('search', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to search decisions.'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        $form = $this->decisionService->getSearchDecisionForm();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $result = $this->decisionService->search($form->getData());

                return new ViewModel(
                    [
                        'result' => $result,
                        'prompt' => $form->getData()['query'],
                        'form' => $form,
                    ],
                );
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ],
        );
    }

    public function authorizationsAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'authorization')) {
            $this->flashMessenger()->addErrorMessage($this->translator->translate('You are not allowed to authorize someone'));
            // Also throw, in case the flashMessenger is not present on this page.
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to authorize someone'),
            );
        }

        $meeting = $this->decisionService->getLatestALV();
        $authorization = null;

        if (null !== $meeting) {
            $authorization = $this->decisionService->getUserAuthorization($meeting);
        }

        $form = $this->decisionService->getAuthorizationForm();

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if (null !== ($authorization = $this->decisionService->createAuthorization($form->getData()))) {
                    $this->flashMessenger()->addSuccessMessage($this->translator->translate('Authorization Successful'));
                    return $this->redirect()->toRoute('decision/authorizations');
                }
            }
        }

        if (null !== $authorization) {
            $form = $this->decisionService->getAuthorizationRevocationForm();
        }

        return new ViewModel(
            [
                'meeting' => $meeting,
                'authorization' => $authorization,
                'form' => $form,
            ],
        );
    }

    public function revokeAuthorizationAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('revoke', 'authorization')) {
            $this->flashMessenger()->addErrorMessage($this->translator->translate('You are not allowed to revoke authorizations.'));
            // Also throw, in case the flashMessenger is not present on this page.
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to revoke authorizations.'),
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            if (null !== ($meeting = $this->decisionService->getLatestALV())) {
                if (null !== ($authorization = $this->decisionService->getUserAuthorization($meeting))) {
                    $form = $this->decisionService->getAuthorizationRevocationForm();
                    $form->setData($request->getPost()->toArray());

                    if ($form->isValid()) {
                        $this->decisionService->revokeAuthorization($authorization);
                        $this->flashMessenger()->addSuccessMessage($this->translator->translate('Revocation Successful'));
                    }
                }
            }
        }

        return $this->redirect()->toRoute('decision/authorizations');
    }

    /**
     * Browse/download files from the set FileReader.
     */
    public function filesAction(): bool|ViewModel|Stream
    {
        if (!$this->decisionService->isAllowedToBrowseFiles()) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to browse files.'),
            );
        }

        $path = $this->params()->fromRoute('path');
        if (null === $path) {
            $path = '';
        }

        if (
            !$this->fileReader->isAllowed($path)
            || 1 === preg_match('(\/\.\.\/|\/\.\.$)', $path)
        ) {
            //File location isn't legal or path contains /../ or /.. at the end.
            //This is illegal for security reasons
            return $this->notFoundAction();
        }

        if ($this->fileReader->isDir($path)) {
            //display the contents of a dir
            $folder = $this->fileReader->listDir($path);
            if (null === $folder) {
                return $this->notFoundAction();
            }

            $trailingSlash = (strlen($path) > 0 && '/' === $path[strlen($path) - 1]);
            $array = explode('/', substr($path, 0, -1));
            $array1 = explode('/', $path);

            return new ViewModel(
                [
                    'folderName' => $trailingSlash ? end($array) : end($array1),
                    'folder' => $folder,
                    'path' => $path,
                    'trailingSlash' => $trailingSlash,
                ],
            );
        }

        //download the file
        $result = $this->fileReader->downloadFile($path);

        return false === $result ? $this->notFoundAction() : $result;
    }
}
