<?php

namespace Decision\Controller;

use Decision\Controller\FileBrowser\FileReader;
use Decision\Service\{
    AclService,
    Decision as DecisionService,
};
use Laminas\Http\Response\Stream;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class DecisionController extends AbstractActionController
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var DecisionService
     */
    private DecisionService $decisionService;

    /**
     * @var FileReader
     */
    private FileReader $fileReader;

    /**
     * DecisionController constructor.
     *
     * @param AclService $aclService
     * @param Translator $translator
     * @param DecisionService $decisionService
     * @param FileReader $fileReader
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        DecisionService $decisionService,
        FileReader $fileReader,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->decisionService = $decisionService;
        $this->fileReader = $fileReader;
    }

    /**
     * Index action, shows meetings.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel(
            [
                'meetings' => $this->decisionService->getMeetings(),
            ]
        );
    }

    /**
     * Download meeting notes.
     */
    public function notesAction(): ViewModel|Stream
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');

        $meeting = $this->decisionService->getMeeting($type, $number);
        if (null !== $meeting) {
            $response = $this->decisionService->getMeetingNotesDownload($meeting);

            if (null !== $response) {
                return $response;
            }
        }

        return $this->notFoundAction();
    }

    public function documentAction(): ViewModel|Stream
    {
        $id = $this->params()->fromRoute('id');

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
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');

        $meeting = $this->decisionService->getMeeting($type, $number);
        if (null === $meeting) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'meeting' => $meeting,
            ]
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

        $request = $this->getRequest();
        $form = $this->decisionService->getSearchDecisionForm();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $result = $this->decisionService->search($form->getData());

                if (null !== $result) {
                    return new ViewModel(
                        [
                            'result' => $result,
                            'form' => $form,
                        ]
                    );
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }

    public function authorizationsAction(): ViewModel
    {
        $meeting = $this->decisionService->getLatestAV();
        $authorization = null;

        if (null !== $meeting) {
            $authorization = $this->decisionService->getUserAuthorization($meeting);
        }

        $form = $this->decisionService->getAuthorizationForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->decisionService->createAuthorization($form->getData())) {
                    return new ViewModel(
                        [
                            'meeting' => $meeting,
                            'authorization' => $authorization,
                        ]
                    );
                }
            }
        }

        return new ViewModel(
            [
                'meeting' => $meeting,
                'authorization' => $authorization,
                'form' => $form,
            ]
        );
    }

    /**
     * Browse/download files from the set FileReader.
     */
    public function filesAction(): bool|ViewModel|Stream
    {
        if (!$this->decisionService->isAllowedToBrowseFiles()) {
            $translator = $this->decisionService->getTranslator();
            throw new NotAllowedException($translator->translate('You are not allowed to browse files.'));
        }
        $path = $this->params()->fromRoute('path');
        if (is_null($path)) {
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
            if (is_null($folder)) {
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
                ]
            );
        }

        //download the file
        $result = $this->fileReader->downloadFile($path);

        return (false === $result) ? $this->notFoundAction() : $result;
    }
}
