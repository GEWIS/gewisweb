<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class PollAdminController extends AbstractActionController
{
    /**
     * List all approved and unapproved polls
     */
    public function listAction()
    {
        $pollService = $this->getPollService();

        $adapter = $pollService->getPaginatorAdapter();
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(15);

        $page = $this->params()->fromRoute('page');

        if ($page) {
            $paginator->setCurrentPageNumber($page);
        }

        $unapprovedPolls = $pollService->getUnapprovedPolls();

        $approvalForm = $pollService->getPollApprovalForm();

        return new ViewModel([
            'unapprovedPolls' => $unapprovedPolls,
            'paginator' => $paginator,
            'approvalForm' => $approvalForm
        ]);
    }

    /**
     * Approve a poll
     */
    public function approveAction()
    {
        if ($this->getRequest()->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $pollService = $this->getPollService();
            $poll = $pollService->getPoll($pollId);
            $pollService->approvePoll($poll, $this->getRequest()->getPost());

            return $this->redirect()->toRoute('admin_poll');
        }
    }

    /**
     * Delete a poll
     */
    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $pollService = $this->getPollService();
            $poll = $pollService->getPoll($pollId);
            $pollService->deletePoll($poll);

            return $this->redirect()->toRoute('admin_poll');
        }
    }

    /**
     * Get the poll service.
     *
     * @return \Frontpage\Service\Poll
     */
    protected function getPollService()
    {
        return $this->getServiceLocator()->get('frontpage_service_poll');
    }
}
