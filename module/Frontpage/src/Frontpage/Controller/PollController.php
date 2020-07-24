<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class PollController extends AbstractActionController
{
    /**
     * Displays the currently active poll
     */
    public function indexAction()
    {
        $pollService = $this->getPollService();

        $poll = $this->obtainPoll();

        if (!is_null($poll)) {
            $details = $pollService->getPollDetails($poll);

            return new ViewModel(array_merge($details, [
                'poll' => $poll,
                'commentForm' => $pollService->getCommentForm()
            ]));
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
        $pollService = $this->getPollService();
        $pollId = $this->params()->fromRoute('poll_id');

        if (is_null($pollId)) {
            return $pollService->getNewestPoll();
        }
        return $pollService->getPoll($pollId);
    }

    /**
     * Submits a poll vote
     */
    public function voteAction()
    {
        $pollId = (int) $this->params('poll_id');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $pollService = $this->getPollService();

            if (isset($request->getPost()['option'])) {
                $optionId = $request->getPost()['option'];
                $pollService->submitVote($pollService->getPollOption($optionId));
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
        $service = $this->getPollService();

        if ($request->isPost()) {
            $pollId = $this->params()->fromRoute('poll_id');
            $service->createComment($pollId, $request->getPost());
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
        $adapter = $this->getPollService()->getPaginatorAdapter();
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(10);

        $page = $this->params()->fromRoute('page');
        if ($page) {
            $paginator->setCurrentPageNumber($page);
        }

        return new ViewModel([
            'paginator' => $paginator
        ]);
    }

    /**
     * Request a poll
     */
    public function requestAction()
    {
        $pollService = $this->getPollService();
        $form = $pollService->getPollForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($pollService->requestPoll($request->getPost())) {
                return new ViewModel([
                    'success' => true,
                ]);
            }
        }

        return new ViewModel([
            'form' => $form,
        ]);
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
