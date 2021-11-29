<?php

namespace Decision\Controller;

use Decision\Controller\FileBrowser\FileReader;
use Decision\Service\Decision as DecisionService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class DecisionController extends AbstractActionController
{
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
     * @param DecisionService $decisionService
     * @param FileReader $fileReader
     */
    public function __construct(
        DecisionService $decisionService,
        FileReader $fileReader
    ) {
        $this->decisionService = $decisionService;
        $this->fileReader = $fileReader;
    }

    /**
     * Index action, shows meetings.
     */
    public function indexAction()
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
    public function notesAction()
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

    public function documentAction()
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
    public function viewAction()
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
    public function searchAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $result = $this->decisionService->search($request->getPost());

            if (null !== $result) {
                return new ViewModel(
                    [
                        'result' => $result,
                        'form' => $this->decisionService->getSearchDecisionForm(),
                    ]
                );
            }
        }

        return new ViewModel(
            [
                'form' => $this->decisionService->getSearchDecisionForm(),
            ]
        );
    }

    public function authorizationsAction()
    {
        $meeting = $this->decisionService->getLatestAV();
        $authorization = null;

        if (null !== $meeting) {
            $authorization = $this->decisionService->getUserAuthorization($meeting->getNumber());
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
    public function filesAction()
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
