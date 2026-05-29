<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Entity\Application\RevisionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

use function assert;

/**
 * When a revision is approved it becomes the publicly live version of its aggregate. The just-approved revision is
 * always the newest in the chain, so it supersedes any previously live revision (which stays as an immutable record).
 *
 * Runs in-memory only; the controller flushes after `$workflow->apply()`.
 */
#[AsEventListener(event: 'workflow.revision.entered.approved')]
final readonly class PromoteLiveRevisionListener
{
    public function __invoke(EnteredEvent $event): void
    {
        $revision = $event->getSubject();
        assert($revision instanceof RevisionInterface);

        $revision->getRevisable()->markRevisionLive($revision);
    }
}
