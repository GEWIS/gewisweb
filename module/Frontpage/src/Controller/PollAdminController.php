<?php

namespace Frontpage\Controller;

use Frontpage\Service\AclService;
use Frontpage\Service\Poll as PollService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class PollAdminController extends AbstractActionController
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
     * @var PollService
     */
    private PollService $pollService;

    /**
     * PollAdminController constructor.
     *
     * @param AclService $aclService
     * @param Translator $translator
     * @param PollService $pollService
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        PollService $pollService,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->pollService = $pollService;
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
            ]
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

        if ($this->getRequest()->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $poll = $this->pollService->getPoll($pollId);

            if (null !== $poll) {
                $approvalForm = $this->pollService->getPollApprovalForm();
                $approvalForm->bind($poll);
                $approvalForm->setData($this->getRequest()->getPost()->toArray());

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

        if ($this->getRequest()->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $poll = $this->pollService->getPoll($pollId);

            if (null !== $poll) {
                $this->pollService->deletePoll($poll);

                return $this->redirect()->toRoute('admin_poll');
            }
        }

        return $this->notFoundAction();
    }
}
