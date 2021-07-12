<?php

namespace Decision\Controller;

use Decision\Service\Decision;
use Doctrine\ORM\NoResultException;
use User\Permissions\NotAllowedException;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class DecisionController extends AbstractActionController
{

    /**
     * @var Decision
     */
    private $decisionService;

    public function __construct(Decision $decisionService)
    {
        $this->decisionService = $decisionService;
    }

    /**
     * Index action, shows meetings.
     */
    public function indexAction()
    {
        return new ViewModel(
            [
            'meetings' => $this->decisionService->getMeetings()
            ]
        );
    }

    /**
     * Download meeting notes
     */
    public function notesAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');

        try {
            $meeting = $this->decisionService->getMeeting($type, $number);
            $response = $this->decisionService->getMeetingNotesDownload($meeting);
            if (is_null($response)) {
                return $this->notFoundAction();
            }

            return $response;
        } catch (NoResultException $e) {
            return $this->notFoundAction();
        }
    }

    public function documentAction()
    {
        $id = $this->params()->fromRoute('id');

        try {
            $meetingDocument = $this->decisionService->getMeetingDocument($id);
            $response = $this->decisionService->getMeetingDocumentDownload($meetingDocument);
            if (is_null($response)) {
                return $this->notFoundAction();
            }

            return $response;
        } catch (NoResultException $e) {
            return $this->notFoundAction();
        }
    }

    /**
     * View a meeting.
     */
    public function viewAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');

        try {
            $meeting = $this->decisionService->getMeeting($type, $number);

            return new ViewModel(
                [
                'meeting' => $meeting
                ]
            );
        } catch (NoResultException $e) {
            return $this->notFoundAction();
        }
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
                    'form' => $this->decisionService->getSearchDecisionForm()
                    ]
                );
            }
        }

        return new ViewModel(
            [
            'form' => $this->decisionService->getSearchDecisionForm()
            ]
        );
    }

    public function authorizationsAction()
    {
        $meeting = $this->decisionService->getLatestAV();
        $authorization = null;
        if (!is_null($meeting)) {
            $authorization = $this->decisionService->getUserAuthorization($meeting->getNumber());
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $authorization = $this->decisionService->createAuthorization($request->getPost());
            if ($authorization) {
                return new ViewModel(
                    [
                    'meeting' => $meeting,
                    'authorization' => $authorization
                    ]
                );
            }
        }

        $form = $this->decisionService->getAuthorizationForm();

        return new ViewModel(
            [
            'meeting' => $meeting,
            'authorization' => $authorization,
            'form' => $form
            ]
        );
    }

    /**
     * Browse/download files from the set FileReader
     */
    public function filesAction()
    {
        if (!$this->decisionService->isAllowedToBrowseFiles()) {
            $translator = $this->decisionService->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to browse files.')
            );
        }
        $path = $this->params()->fromRoute('path');
        if (is_null($path)) {
            $path = '';
        }

        $fileReader = $this->getServiceLocator()->get('decision_fileReader');
        if (!$fileReader->isAllowed($path) || preg_match('(\/\.\.\/|\/\.\.$)', $path) === 1) {
            //File location isn't legal or path contains /../ or /.. at the end.
            //This is illegal for security reasons
            return $this->notFoundAction();
        }
        if ($fileReader->isDir($path)) {
            //display the contents of a dir
            $folder = $fileReader->listDir($path);
            if (is_null($folder)) {
                return $this->notFoundAction();
            }
            $trailingSlash = (strlen($path) > 0 && $path[strlen($path) - 1] === '/');
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
        $result = $fileReader->downloadFile($path);
        return is_null($result) ? $this->notFoundAction() : $result;
    }
}
