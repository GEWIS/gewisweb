<?php

namespace Decision\Controller;

use Decision\Service\Decision as DecisionService;
use Laminas\Http\Response;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AdminController extends AbstractActionController
{
    /**
     * @var DecisionService
     */
    private DecisionService $decisionService;

    /**
     * AdminController constructor.
     *
     * @param DecisionService $decisionService
     */
    public function __construct(DecisionService $decisionService)
    {
        $this->decisionService = $decisionService;
    }

    /**
     * Notes upload action.
     */
    public function notesAction()
    {
        $form = $this->decisionService->getNotesForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );
            $form->setData($post);

            if ($form->isValid()) {
                if ($this->decisionService->uploadNotes($form->getData())) {
                    return new ViewModel(
                        [
                            'success' => true,
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

    /**
     * Document upload action.
     */
    public function documentAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');
        $meetings = $this->decisionService->getMeetingsByType('AV');
        $meetings = array_merge($meetings, $this->decisionService->getMeetingsByType('VV'));

        if (null === $number && !empty($meetings)) {
            $number = $meetings[0]->getNumber();
            $type = $meetings[0]->getType();
        }

        $form = $this->decisionService->getDocumentForm();

        $request = $this->getRequest();
        $success = false;
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );
            $form->setData($post);

            if ($form->isValid()) {
                if ($this->decisionService->uploadDocument($form->getData())) {
                    $success = true;
                }
            }
        }

        $meeting = $this->decisionService->getMeeting($type, $number);

        return new ViewModel(
            [
                'form' => $form,
                'meetings' => $meetings,
                'meeting' => $meeting,
                'number' => $number,
                'success' => $success,
                'reorderDocumentForm' => $this->decisionService->getReorderDocumentForm(),
            ]
        );
    }

    /**
     * TODO: Non-idempotent requests should be POST, not GET.
     */
    public function deleteDocumentAction()
    {
        $this->decisionService->deleteDocument($this->getRequest()->getPost()->toArray());

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
        $moveDown = 'down' === $data['direction'];

        // Update ordering document
        $this->decisionService->changePositionDocument($id, $moveDown);

        return $this->getResponse()->setStatusCode(Response::STATUS_CODE_204); // No Content (OK)
    }

    public function authorizationsAction()
    {
        $meetings = $this->decisionService->getMeetingsByType('AV');
        $number = $this->params()->fromRoute('number');
        $authorizations = [];

        if (null === $number && !empty($meetings)) {
            $number = $meetings[0]->getNumber();
        }

        if (null !== $number) {
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
