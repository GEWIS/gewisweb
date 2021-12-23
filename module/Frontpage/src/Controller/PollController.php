<?php

namespace Frontpage\Controller;

use Frontpage\Form\PollComment as PollCommentForm;
use Frontpage\Model\Poll as PollModel;
use Frontpage\Service\{
    AclService,
    Poll as PollService,
};
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Paginator\Paginator;
use Laminas\Stdlib\ResponseInterface;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class PollController extends AbstractActionController
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
     * @var PollCommentForm
     */
    private PollCommentForm $pollCommentForm;

    /**
     * @var PollService
     */
    private PollService $pollService;

    /**
     * PollController constructor.
     *
     * @param AclService $aclService
     * @param Translator $translator
     * @param PollCommentForm $pollCommentForm
     * @param PollService $pollService
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        PollCommentForm $pollCommentForm,
        PollService $pollService,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->pollCommentForm = $pollCommentForm;
        $this->pollService = $pollService;
    }

    /**
     * Displays the currently active poll.
     */
    public function indexAction(): ViewModel
    {
        $poll = $this->obtainPoll();

        if (!is_null($poll)) {
            $details = $this->pollService->getPollDetails($poll);

            return new ViewModel(
                array_merge(
                    $details,
                    [
                        'poll' => $poll,
                        'commentForm' => $this->pollCommentForm,
                    ]
                )
            );
        }

        return new ViewModel();
    }

    /**
     * Get the right from the route.
     *
     * @return PollModel|null
     */
    public function obtainPoll(): ?PollModel
    {
        $pollId = $this->params()->fromRoute('poll_id');

        if (is_null($pollId)) {
            return $this->pollService->getNewestPoll();
        }

        return $this->pollService->getPoll($pollId);
    }

    /**
     * Submits a poll vote.
     */
    public function voteAction(): Response|ResponseInterface
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
    public function commentAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'poll_comment')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create comments on this poll')
            );
        }

        $pollId = $this->params()->fromRoute('poll_id');
        $poll = $this->pollService->getPoll($pollId);

        if (null === $poll) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->pollCommentForm->setData($request->getPost()->toArray());

            if ($this->pollCommentForm->isValid()) {
                if ($this->pollService->createComment($poll, $this->pollCommentForm->getData())) {
                    $this->pollCommentForm->setData(['author' => '', 'content' => '']);
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
            ]
        );
    }

    /**
     * Request a poll.
     */
    public function requestAction(): ViewModel
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
