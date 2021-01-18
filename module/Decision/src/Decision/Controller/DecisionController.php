<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Decision\Controller\FileBrowser\LocalFileReader as LocalFileReader;

class DecisionController extends AbstractActionController
{

    /**
     * Index action, shows meetings.
     */
    public function indexAction()
    {
        return new ViewModel([
            'meetings' => $this->getDecisionService()->getMeetings()
        ]);
    }

    /**
     * Download meeting notes
     */
    public function notesAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');

        try {
            $meeting = $this->getDecisionService()->getMeeting($type, $number);
            $response = $this->getDecisionService()->getMeetingNotesDownload($meeting);
            if (is_null($response)) {
                return $this->notFoundAction();
            }

            return $response;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }

    }

    public function documentAction()
    {
        $id = $this->params()->fromRoute('id');

        try {
            $meetingDocument = $this->getDecisionService()->getMeetingDocument($id);
            $response = $this->getDecisionService()->getMeetingDocumentDownload($meetingDocument);
            if (is_null($response)) {
                return $this->notFoundAction();
            }

            return $response;
        } catch (\Doctrine\ORM\NoResultException $e) {
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
        $service = $this->getDecisionService();

        try {
            $meeting = $service->getMeeting($type, $number);

            return new ViewModel([
                'meeting' => $meeting
            ]);
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }
    }

    /**
     * Search decisions.
     */
    public function searchAction()
    {
        $service = $this->getDecisionService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $result = $service->search($request->getPost());

            if (null !== $result) {
                return new ViewModel([
                    'result' => $result,
                    'form' => $service->getSearchDecisionForm()
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getSearchDecisionForm()
        ]);
    }

    public function authorizationsAction()
    {
        $meeting = $this->getDecisionService()->getLatestAV();
        $authorization = null;
        if (!is_null($meeting)) {
            $authorization = $this->getDecisionService()->getUserAuthorization($meeting->getNumber());
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $authorization = $this->getDecisionService()->createAuthorization($request->getPost());
            if ($authorization) {
                return new ViewModel([
                    'meeting' => $meeting,
                    'authorization' => $authorization
                ]);
            }
        }

        $form = $this->getDecisionService()->getAuthorizationForm();

        return new ViewModel([
            'meeting' => $meeting,
            'authorization' => $authorization,
            'form' => $form
        ]);
    }

    /**
     * Browse/download files from the set FileReader
     */
    public function filesAction()
    {
        if (!$this->getDecisionService()->isAllowedToBrowseFiles()) {
            $translator = $this->getDecisionService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
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
            return new ViewModel([
                'folderName' =>  $trailingSlash ? end(explode('/', substr($path, 0, -1))) : end(explode('/', $path)),
                'folder' => $folder,
                'path' => $path,
                'trailingSlash' => $trailingSlash,
            ]);
        }
        //download the file
        $result = $fileReader->downloadFile($path);
        return is_null($result) ? $this->notFoundAction() : $result;
    }

    /**
     * Get the decision service.
     */
    public function getDecisionService()
    {
        return $this->getServiceLocator()->get('decision_service_decision');
    }

}
