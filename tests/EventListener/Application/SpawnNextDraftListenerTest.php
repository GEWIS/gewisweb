<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Application;

use App\Entity\Application\RevisionInterface;
use App\EventListener\Application\SpawnNextDraftListener;
use App\Workflow\RevisionClonerInterface;
use App\Workflow\RevisionClonerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Marking;

/**
 * When the board requests changes, a fresh draft (N+1) must be spawned through the cloner registry and persisted -- but
 * NOT flushed, since the controller commits the new draft together with the status change in one transaction. These
 * tests pin both halves of that contract.
 */
final class SpawnNextDraftListenerTest extends TestCase
{
    public function testClonesTheNextDraftThroughTheRegistryAndPersistsItWithoutFlushing(): void
    {
        $source = self::createStub(RevisionInterface::class);
        $draft = self::createStub(RevisionInterface::class);

        $cloner = self::createStub(RevisionClonerInterface::class);
        $cloner->method('supports')->willReturn(true);
        $cloner->method('cloneAsDraft')->willReturn($draft);
        $registry = new RevisionClonerRegistry([$cloner]);

        $entityManager = self::createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with($draft);
        $entityManager->expects(self::never())->method('flush');

        $listener = new SpawnNextDraftListener(
            $registry,
            $entityManager,
        );
        $listener(new EnteredEvent(
            $source,
            new Marking([]),
        ));
    }
}
