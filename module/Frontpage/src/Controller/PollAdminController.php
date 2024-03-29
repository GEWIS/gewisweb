<?php

declare(strict_types=1);

namespace Frontpage\Controller;

use Frontpage\Service\AclService;
use Frontpage\Service\Poll as PollService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class PollAdminController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly PollService $pollService,
    ) {
    }

    /**
     * List all approved and unapproved polls.
     */
    public function listAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('approve', 'poll')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer polls'));
        }

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
            ],
        );
    }

    /**
     * Approve a poll.
     */
    public function approveAction(): ViewModel|Response
    {
        if (!$this->aclService->isAllowed('approve', 'poll')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to approve polls'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $pollId = (int) $this->params()->fromRoute('poll_id');
            $poll = $this->pollService->getPoll($pollId);

            if (null !== $poll) {
                $approvalForm = $this->pollService->getPollApprovalForm();
                $approvalForm->bind($poll);
                $approvalForm->setData($request->getPost()->toArray());

                if ($approvalForm->isValid()) {
                    if ($this->pollService->approvePoll($poll)) {
                        return $this->redirect()->toRoute('admin_poll');
                    }
                }
            }
        }

        return $this->notFoundAction();
    }

    /**
     * Delete a poll.
     */
    public function deleteAction(): ViewModel|Response
    {
        if (!$this->aclService->isAllowed('delete', 'poll')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete polls'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $pollId = (int) $this->params()->fromRoute('poll_id');
            $poll = $this->pollService->getPoll($pollId);

            if (null !== $poll) {
                $this->pollService->deletePoll($poll);

                return $this->redirect()->toRoute('admin_poll');
            }
        }

        return $this->notFoundAction();
    }
}
