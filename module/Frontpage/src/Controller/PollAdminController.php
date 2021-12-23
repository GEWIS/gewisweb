<?php

namespace Frontpage\Controller;

use Frontpage\Service\Poll as PollService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class PollAdminController extends AbstractActionController
{
    /**
     * @var PollService
     */
    private PollService $pollService;

    /**
     * PollAdminController constructor.
     *
     * @param PollService $pollService
     */
    public function __construct(PollService $pollService)
    {
        $this->pollService = $pollService;
    }

    /**
     * List all approved and unapproved polls.
     */
    public function listAction(): ViewModel
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
    public function approveAction(): Response
    {
        if ($this->getRequest()->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $poll = $this->pollService->getPoll($pollId);
            $this->pollService->approvePoll($poll, $this->getRequest()->getPost());

            return $this->redirect()->toRoute('admin_poll');
        }
        throw new NotAllowedException();
    }

    /**
     * Delete a poll.
     */
    public function deleteAction(): Response
    {
        if ($this->getRequest()->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $poll = $this->pollService->getPoll($pollId);
            $this->pollService->deletePoll($poll);

            return $this->redirect()->toRoute('admin_poll');
        }
        throw new NotAllowedException();
    }
}
