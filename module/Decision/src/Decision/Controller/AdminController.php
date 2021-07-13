<?php

namespace Decision\Controller;

use Decision\Service\Decision;
use Laminas\Http\Response;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AdminController extends AbstractActionController
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
     * Notes upload action.
     */
    public function notesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->decisionService->uploadNotes($request->getPost(), $request->getFiles())) {
                return new ViewModel(
                    [
                    'success' => true,
                    ]
                );
            }
        }

        return new ViewModel(
            [
            'form' => $this->decisionService->getNotesForm(),
            ]
        );
    }

    /**
     * Document upload action.
     */
    public function documentAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');
        $meetings = $this->decisionService->getMeetingsByType('AV');
        $meetings = array_merge($meetings, $this->decisionService->getMeetingsByType('VV'));
        if (is_null($number) && !empty($meetings)) {
            $number = $meetings[0]->getNumber();
            $type = $meetings[0]->getType();
        }
        $request = $this->getRequest();
        $success = false;
        if ($request->isPost()) {
            if ($this->decisionService->uploadDocument($request->getPost(), $request->getFiles())) {
                $success = true;
            }
        }
        $meeting = $this->decisionService->getMeeting($type, $number);

        return new ViewModel(
            [
            'form' => $this->decisionService->getDocumentForm(),
            'meetings' => $meetings,
            'meeting' => $meeting,
            'number' => $number,
            'success' => $success,
            'reorderDocumentForm' => $this->decisionService->getReorderDocumentForm(),
            ]
        );
    }

    public function deleteDocumentAction()
    {
        $this->decisionService->deleteDocument($this->getRequest()->getPost());

        return $this->redirect()->toRoute('admin_decision/document');
    }

    public function changePositionDocumentAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->getResponse()->setStatusCode(Response::STATUS_CODE_405); // Method Not Allowed
        }

        $form = $this->decisionService->getReorderDocumentForm()
            ->setData($this->getRequest()->getPost());

        if (!$form->isValid()) {
            return $this->getResponse()
                ->setStatusCode(Response::STATUS_CODE_400) // Bad Request
                ->setContent(Json::encode($form->getMessages()));
        }

        $data = $form->getData();
        $id = $data['document'];
        $moveDown = ('down' === $data['direction']) ? true : false;

        // Update ordering document
        $this->decisionService->changePositionDocument($id, $moveDown);

        return $this->getResponse()->setStatusCode(Response::STATUS_CODE_204); // No Content (OK)
    }

    public function authorizationsAction()
    {
        $meetings = $this->decisionService->getMeetingsByType('AV');
        $number = $this->params()->fromRoute('number');
        $authorizations = [];
        if (is_null($number) && !empty($meetings)) {
            $number = $meetings[0]->getNumber();
        }

        if (!is_null($number)) {
            $authorizations = $this->decisionService->getAllAuthorizations($number);
        }

        return new ViewModel(
            [
            'meetings' => $meetings,
            'authorizations' => $authorizations,
            'number' => $number,
            ]
        );
    }
}
