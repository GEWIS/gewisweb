<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Entity\Application\RevisionInterface;
use App\Workflow\RevisionClonerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

use function assert;

/**
 * When the board requests changes, the current revision becomes an immutable record and a fresh draft (N+1), linked to
 * it, is spawned for the author to edit and resubmit.
 *
 * The new revision is persisted here but not flushed; the controller flushes after `$workflow->apply()` so the
 * status change and the new draft commit in one transaction.
 */
#[AsEventListener(event: 'workflow.revision.entered.changes-requested')]
final readonly class SpawnNextDraftListener
{
    public function __construct(
        private RevisionClonerRegistry $clonerRegistry,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(EnteredEvent $event): void
    {
        $source = $event->getSubject();
        assert($source instanceof RevisionInterface);

        // The cloner carries the original author forward, so they can address the requested changes and resubmit.
        $draft = $this->clonerRegistry->cloneAsDraft($source);

        $this->entityManager->persist($draft);
    }
}
