<?php

namespace Frontpage\Controller;

use Frontpage\Form\PollComment;
use Frontpage\Service\Poll;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class PollController extends AbstractActionController
{

    /**
     * @var Poll
     */
    private $pollService;

    /**
     * @var PollComment
     */
    private $pollCommentForm;

    public function __construct(Poll $pollService, PollComment $pollCommentForm)
    {
        $this->pollService = $pollService;
        $this->pollCommentForm = $pollCommentForm;
    }

    /**
     * Displays the currently active poll
     */
    public function indexAction()
    {
        $poll = $this->obtainPoll();

        if (!is_null($poll)) {
            $details = $this->pollService->getPollDetails($poll);

            return new ViewModel(
                array_merge(
                    $details,
                    [
                    'poll' => $poll,
                    'commentForm' => $this->pollCommentForm
                    ]
                )
            );
        }

        return new ViewModel();
    }

    /**
     * Get the right from the route.
     *
     * @param int $pollId
     */
    public function obtainPoll()
    {
        $pollId = $this->params()->fromRoute('poll_id');

        if (is_null($pollId)) {
            return $this->pollService->getNewestPoll();
        }
        return $this->pollService->getPoll($pollId);
    }

    /**
     * Submits a poll vote
     */
    public function voteAction()
    {
        $pollId = (int)$this->params('poll_id');
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (isset($request->getPost()['option'])) {
                $optionId = $request->getPost()['option'];
                $this->pollService->submitVote($this->pollService->getPollOption($optionId));
            }
        }

        $this->redirect()->toRoute('poll/view', ['poll_id' => $pollId]);
        return $this->getResponse();
    }

    /**
     * Submits a comment.
     */
    public function commentAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $this->pollService->createComment($pollId, $request->getPost());
        }

        // execute the index action and show the poll
        $vm = $this->indexAction();
        $vm->setTemplate('frontpage/poll/index');
        return $vm;
    }

    /**
     * View all previous polls
     */
    public function historyAction()
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
            'paginator' => $paginator
            ]
        );
    }

    /**
     * Request a poll
     */
    public function requestAction()
    {
        $form = $this->pollService->getPollForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->pollService->requestPoll($request->getPost())) {
                return new ViewModel(
                    [
                    'success' => true,
                    ]
                );
            }
        }

        return new ViewModel(
            [
            'form' => $form,
            ]
        );
    }
}
