<?php

declare(strict_types=1);

namespace Frontpage\Controller;

use Frontpage\Form\PollComment as PollCommentForm;
use Frontpage\Model\Poll as PollModel;
use Frontpage\Service\AclService;
use Frontpage\Service\Poll as PollService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;
use Override;
use User\Permissions\NotAllowedException;

use function array_merge;
use function intval;

class PollController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly PollCommentForm $pollCommentForm,
        private readonly PollService $pollService,
    ) {
    }

    /**
     * Displays the currently active poll.
     */
    #[Override]
    public function indexAction(): ViewModel
    {
        $poll = $this->obtainPoll();

        if (null !== $poll) {
            $details = $this->pollService->getPollDetails($poll);

            return new ViewModel(
                array_merge(
                    $details,
                    [
                        'poll' => $poll,
                        'commentForm' => $poll->isActive() ? $this->pollCommentForm : null,
                    ],
                ),
            );
        }

        return new ViewModel();
    }

    /**
     * Get the right from the route.
     */
    public function obtainPoll(): ?PollModel
    {
        $pollId = (int) $this->params()->fromRoute('poll_id');

        if (0 === $pollId) {
            return $this->pollService->getNewestPoll();
        }

        return $this->pollService->getPoll($pollId);
    }

    /**
     * Submits a poll vote.
     */
    public function voteAction(): Response
    {
        $pollId = (int) $this->params()->fromRoute('poll_id');
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (isset($request->getPost()['option'])) {
                $optionId = intval($request->getPost()['option']);
                $this->pollService->submitVote($this->pollService->getPollOption($optionId));
            }
        }

        return $this->redirect()->toRoute('poll/view', ['poll_id' => $pollId]);
    }

    /**
     * Submits a comment.
     */
    public function commentAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'poll_comment')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create comments on this poll'),
            );
        }

        $pollId = (int) $this->params()->fromRoute('poll_id');
        $poll = $this->pollService->getPoll($pollId);

        if (
            null === $poll
            || !$poll->isActive()
        ) {
            return $this->notFoundAction();
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->pollCommentForm->setData($request->getPost()->toArray());

            if ($this->pollCommentForm->isValid()) {
                if ($this->pollService->createComment($poll, $this->pollCommentForm->getData())) {
                    return $this->redirect()->toRoute('poll');
                }
            }
        }

        // execute the index action and show the poll
        $vm = $this->indexAction();
        $vm->setTemplate('frontpage/poll/index');

        return $vm;
    }

    /**
     * View all previous polls.
     */
    public function historyAction(): ViewModel
    {
        $adapter = $this->pollService->getPaginatorAdapter();
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(10);

        $page = $this->params()->fromRoute('page');
        if ($page) {
            $paginator->setCurrentPageNumber($page);
        }

        return new ViewModel(
            [
                'paginator' => $paginator,
            ],
        );
    }

    /**
     * Request a poll.
     */
    public function requestAction(): ViewModel
    {
        $form = $this->pollService->getPollForm();

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->pollService->requestPoll($request->getPost())) {
                return new ViewModel(
                    [
                        'success' => true,
                    ],
                );
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ],
        );
    }
}
