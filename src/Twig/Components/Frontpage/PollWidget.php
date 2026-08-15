<?php

declare(strict_types=1);

namespace App\Twig\Components\Frontpage;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollOption;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Frontpage\PollRepository;
use App\Service\Frontpage\PollService;
use App\Twig\Components\Concerns\FlashesTrait;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A poll as it is shown and answered: the question, the answers to pick from, and how the association answered so far.
 * Answering updates the panel in place.
 *
 * The same component serves the rail on the front page and the poll's own page; `detailed` decides which. The
 * results are on both, because how a poll is going is the point of reading one.
 *
 * Deliberately not gated as a whole, because a passer-by should still see what the association is being asked; they
 * simply get no controls, and {@see self::vote()} checks for itself rather than trusting that.
 *
 * Answering is a live action and nothing else, so a reader without JavaScript sees the question and the results but
 * cannot answer.
 */
#[AsLiveComponent(
    name: 'Frontpage:PollWidget',
    template: 'components/Frontpage/PollWidget.html.twig',
)]
final class PollWidget
{
    use DefaultActionTrait;
    use FlashesTrait;

    #[LiveProp]
    public Poll $poll;

    /** The poll's own page shows the whole card; the front page shows the short one. */
    #[LiveProp]
    public bool $detailed = false;

    /** Component-local, transient: what went wrong, shown on the render right after an answer. */
    public ?string $problem = null;

    private bool $chosenResolved = false;
    private ?PollOption $chosen = null;

    public function __construct(
        private readonly Security $security,
        private readonly PollService $pollService,
        private readonly PollRepository $pollRepository,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * A live action re-hydrates the poll bare, so the render after it would count every answer's votes one query at
     * a time. The initial render is primed by whoever mounts the component instead.
     */
    #[PostHydrate]
    public function primeResults(): void
    {
        $this->pollRepository->primeResults([$this->poll]);
    }

    #[LiveAction]
    public function vote(
        #[LiveArg]
        int $option,
    ): ?Response {
        if (!$this->canAnswer()) {
            throw new AccessDeniedException();
        }

        // Looked up outside the catch below: an AccessDeniedException is a RuntimeException too, and refusing an
        // answer that is not this poll's is not something to turn into a message about having already answered.
        $chosen = $this->find($option);

        try {
            $this->pollService->submitVote(
                $this->poll,
                $chosen,
                $this->member()->getMember(),
            );
        } catch (UniqueConstraintViolationException) {
            // A second tab answered while this one was deciding and the unique index caught it. The entity manager is
            // closed now, so this panel cannot be re-rendered: flash and send the reader to the poll, which reloads
            // it in its answered state on a fresh manager.
            return $this->answeredElsewhere();
        } catch (RuntimeException) {
            $this->problem = $this->translator->trans('You have already answered this poll.');
        }

        // The answer is what the render right after this is about, so what was resolved before it is out of date: the
        // results were counted on the way in, without the answer that has just been given.
        $this->chosenResolved = false;
        $this->chosen = null;
        $this->primeResults();

        return null;
    }

    /**
     * The answer this reader gave, or null when they have not answered or are not signed in. Resolved once: the
     * template asks for it, and so does every `canAnswer()` beside it.
     */
    public function chosen(): ?PollOption
    {
        if ($this->chosenResolved) {
            return $this->chosen;
        }

        $this->chosenResolved = true;
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $this->chosen = $this->pollService->votedOption(
                $this->poll,
                $user->getMember(),
            );
        }

        return $this->chosen;
    }

    public function canAnswer(): bool
    {
        return $this->poll->isActive()
            && $this->security->isGranted(UserRoles::User->value)
            && null === $this->chosen();
    }

    /**
     * A poll closes on its date, so the day it expires counts as no days left.
     */
    public function daysLeft(): ?int
    {
        $expiryDate = $this->poll->getExpiryDate();

        if (
            null === $expiryDate
            || !$this->poll->isActive()
        ) {
            return null;
        }

        return new DateTime('today')->diff($expiryDate)->days;
    }

    private function answeredElsewhere(): RedirectResponse
    {
        $this->flash(
            AlertTypes::Warning->value,
            $this->translator->trans('You have already answered this poll.'),
        );

        return new RedirectResponse(
            $this->urlGenerator->generate(
                'poll/view',
                ['poll' => $this->poll->getId()],
            ),
        );
    }

    private function find(int $option): PollOption
    {
        foreach ($this->poll->getOptions() as $candidate) {
            if ($candidate->getId() !== $option) {
                continue;
            }

            return $candidate;
        }

        throw new AccessDeniedException();
    }

    private function member(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException();
        }

        return $user;
    }
}
