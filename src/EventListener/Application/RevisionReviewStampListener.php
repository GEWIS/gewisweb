<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Entity\Application\RevisionInterface;
use App\Entity\User\User;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\TransitionEvent;

use function assert;
use function in_array;

/**
 * Records who reviewed a revision and when, on every board decision (`approve` / `reject` / `request_changes`). The
 * controller flushes after `$workflow->apply()`, so the stamp is persisted together with the new status.
 */
final readonly class RevisionReviewStampListener
{
    public function __construct(
        private Security $security,
    ) {
    }

    /**
     * The generic transition event fires for every `revision` transition, so keep an allowlist: only the board
     * decisions (`approve` / `reject` / `request_changes`) stamp a reviewer; `submit`, `start_review`, `close` do not.
     */
    private const array REVIEW_TRANSITIONS = [
        'approve',
        'reject',
        'request_changes',
    ];

    #[AsEventListener(event: 'workflow.revision.transition')]
    public function onTransition(TransitionEvent $event): void
    {
        $transition = $event->getTransition();
        if (
            null === $transition
            || !in_array(
                $transition->getName(),
                self::REVIEW_TRANSITIONS,
                true,
            )
        ) {
            return;
        }

        $revision = $event->getSubject();
        assert($revision instanceof RevisionInterface);

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $revision->setReviewer($user->getMember());
        }

        $revision->setReviewedAt(new DateTime());
    }
}
