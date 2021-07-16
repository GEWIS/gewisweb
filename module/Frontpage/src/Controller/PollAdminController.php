<?php

namespace Frontpage\Controller;

use Frontpage\Service\Poll;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;

class PollAdminController extends AbstractActionController
{
    /**
     * @var Poll
     */
    private $pollService;

    public function __construct(Poll $pollService)
    {
        $this->pollService = $pollService;
    }

    /**
     * List all approved and unapproved polls.
     */
    public function listAction()
    {
        $adapter = $this->pollService->getPaginatorAdapter();
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(15);

        $page = $this->params()->fromRoute('page');

        if ($page) {
            $paginator->setCurrentPageNumber($page);
        }

        $unapprovedPolls = $this->pollService->getUnapprovedPolls();

        $approvalForm = $this->pollService->getPollApprovalForm();

        return new ViewModel(
            [
                'unapprovedPolls' => $unapprovedPolls,
                'paginator' => $paginator,
                'approvalForm' => $approvalForm,
            ]
        );
    }

    /**
     * Approve a poll.
     */
    public function approveAction()
    {
        if ($this->getRequest()->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $poll = $this->pollService->getPoll($pollId);
            $this->pollService->approvePoll($poll, $this->getRequest()->getPost());

            return $this->redirect()->toRoute('admin_poll');
        }
    }

    /**
     * Delete a poll.
     */
    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $poll = $this->pollService->getPoll($pollId);
            $this->pollService->deletePoll($poll);

            return $this->redirect()->toRoute('admin_poll');
        }
    }
}
