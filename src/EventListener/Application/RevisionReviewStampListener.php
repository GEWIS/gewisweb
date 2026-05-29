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

    #[AsEventListener(event: 'workflow.revision.transition.approve')]
    public function onApprove(TransitionEvent $event): void
    {
        $this->stamp($event);
    }

    #[AsEventListener(event: 'workflow.revision.transition.reject')]
    public function onReject(TransitionEvent $event): void
    {
        $this->stamp($event);
    }

    #[AsEventListener(event: 'workflow.revision.transition.request_changes')]
    public function onRequestChanges(TransitionEvent $event): void
    {
        $this->stamp($event);
    }

    private function stamp(TransitionEvent $event): void
    {
        $revision = $event->getSubject();
        assert($revision instanceof RevisionInterface);

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $revision->setReviewer($user->getMember());
        }

        $revision->setReviewedAt(new DateTime());
    }
}
