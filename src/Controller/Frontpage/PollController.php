<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollOption;
use App\Entity\Frontpage\PollRevision;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Frontpage\PollRequestType;
use App\Repository\Frontpage\PollRepository;
use App\Security\Application\RevisionVoter;
use App\Service\Frontpage\PollService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function intval;

/**
 * The polls as members see them: everything the association was ever asked, a page per question, and the form for
 * putting one forward.
 *
 * Anybody may read a poll and its results. Answering one, and everything else that leaves a trace, is for members.
 */
#[Route(
    path: '/polls',
    name: 'poll/',
)]
class PollController extends AbstractController
{
    private const int EARLIER_LIMIT = 3;

    public function __construct(
        private readonly PollRepository $pollRepository,
        private readonly PollService $pollService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        // The archive component keeps the whole of this page's state in the query string, so the only thing handed to
        // this page is the poll that is running: it sits above the archive and is the same one whatever is searched
        // for, which is why it is not the component's to fetch again on every keystroke.
        return $this->render(
            'frontpage/poll/index.html.twig',
            ['current' => $this->pollRepository->findCurrentPoll()],
        );
    }

    /**
     * Ask the association something. A poll is written and submitted in one go, so leaving this page without pressing
     * the button leaves nothing behind.
     *
     * A question the board turned down is asked again from here with `?from=`, which continues that poll's chain so
     * the board reads the new wording against what it refused.
     */
    #[Route(
        path: '/request',
        name: 'request',
        methods: [
            'GET',
            'POST',
        ],
    )]
    #[IsGranted(UserRoles::User->value)]
    public function request(
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        $previous = $this->resubmittablePoll(
            $request,
            $user,
        );
        $revision = new PollRevision();

        // A brand-new question starts with somewhere to type the first two answers, since a poll needs at least two.
        if (null === $previous) {
            $revision->addOption(new PollOption());
            $revision->addOption(new PollOption());
        } else {
            $this->prefillFrom(
                $revision,
                $previous,
            );
        }

        $form = $this->createForm(
            PollRequestType::class,
            $revision,
        )->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $poll = $this->pollService->requestPoll(
                $revision,
                $user->getMember(),
                $previous,
            );

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('Your poll was sent to the board. You will hear back once they decide.'),
            );

            return $this->redirectToRoute(
                'poll/view',
                ['poll' => $poll->getId()],
            );
        }

        return $this->render(
            'frontpage/poll/request.html.twig',
            [
                'form' => $form->createView(),
                'previous' => $previous,
            ],
        );
    }

    #[Route(
        path: '/{poll}',
        name: 'view',
        requirements: ['poll' => '\d+'],
    )]
    public function view(
        Poll $poll,
        #[CurrentUser]
        ?User $user,
    ): Response {
        // A question the board has not agreed to is not on the website yet. Whoever asked it still gets to see where
        // it stands, and so does the board; to everybody else the poll does not exist.
        if (
            null === $poll->getLiveRevision()
            && !$this->isGranted(
                RevisionVoter::VIEW,
                $poll,
            )
        ) {
            throw $this->createNotFoundException();
        }

        // One query for the answers and their votes, instead of a count apiece while the page renders.
        $this->pollRepository->primeResults([$poll]);

        return $this->render(
            'frontpage/poll/view.html.twig',
            [
                'poll' => $poll,
                'earlier' => $this->earlierThan($poll),
                'canAskAgain' => null !== $user
                    && null !== $this->rejectedChainOf(
                        $poll,
                        $user,
                    ),
            ],
        );
    }

    /**
     * A few questions from around the same time, so a poll sits in the archive rather than on its own.
     *
     * @return Poll[]
     */
    private function earlierThan(Poll $poll): array
    {
        return $this->pollRepository->findEarlierThan(
            $poll,
            self::EARLIER_LIMIT,
        );
    }

    /**
     * The poll this request continues, when the reader came from one of their own that was turned down. Anything else
     * starts a new poll, since a chain belongs to whoever asked and only carries on where the board said no.
     */
    private function resubmittablePoll(
        Request $request,
        User $user,
    ): ?Poll {
        $id = $request->query->get('from');
        if (null === $id) {
            return null;
        }

        $poll = $this->pollRepository->find(intval($id));
        if (null === $poll) {
            return null;
        }

        return $this->rejectedChainOf(
            $poll,
            $user,
        );
    }

    /**
     * This poll, when it is one of the reader's own that the board turned down and can therefore be asked again;
     * null otherwise.
     */
    private function rejectedChainOf(
        Poll $poll,
        User $user,
    ): ?Poll {
        if ($poll->getCreator()->getLidnr() !== $user->getLidnr()) {
            return null;
        }

        $head = $poll->getCurrentRevision();
        if (
            null === $head
            || RevisionStatus::Rejected !== $head->getStatus()
        ) {
            return null;
        }

        return $poll;
    }

    /**
     * Start the new question off with what the previous one said, so a rejection is answered by editing rather than by
     * typing it all again. The texts are copied rather than shared: the revision they came from is a record now.
     */
    private function prefillFrom(
        PollRevision $revision,
        Poll $poll,
    ): void {
        $head = $poll->getCurrentRevision();
        if (null === $head) {
            return;
        }

        $revision->setQuestion($head->getQuestion()->copy());

        foreach ($head->getOptions() as $option) {
            $copy = new PollOption();
            $copy->setText($option->getText()->copy());
            $revision->addOption($copy);
        }
    }
}
