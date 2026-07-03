<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Application;

use App\Entity\Activity\Activity;
use App\Entity\Application\EditLock;
use App\Entity\Application\RevisableInterface;
use App\Entity\User\User;
use App\Repository\Application\EditLockRepository;
use App\Service\Application\EditLockService;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;

use function sprintf;

/**
 * The exclusive edit lock guards concurrent editing of a revisable aggregate, and its acquisition serialises on a
 * MariaDB named lock (`GET_LOCK`/`RELEASE_LOCK`) that SQLite cannot provide, so it is pinned against the real
 * database. Covers the full lifecycle: first acquisition, same-holder refresh, the block when another holds an alive
 * lock, the reviewer force take-over, the silent take-over of a stale (un-pinged) lock, the heartbeat, release, the
 * blocking-lock query and the unflushed purge. An activity stands in for the aggregate; the service is agnostic to the
 * resource type.
 */
final class EditLockServiceTest extends DatabaseTestCase
{
    public function testAcquireCreatesALockHeldByThePrincipal(): void
    {
        $resource = $this->aResource();
        $holder = $this->users()[0];

        $lock = $this->service()->acquire(
            $resource,
            $holder,
        );

        self::assertInstanceOf(
            EditLock::class,
            $lock,
        );
        self::assertSame(
            $holder->getLidnr(),
            $lock->getLockedBy()?->getLidnr(),
        );
        self::assertNull($lock->getLockedByCompanyUser());
        self::assertTrue($this->service()->isAlive($lock));
        // It is persisted under the resource's key, so a later look-up finds the same row.
        self::assertNotNull($this->locks()->findOneByResource(
            $resource->getResourceId(),
            (int) $resource->getId(),
        ));
    }

    public function testReacquireByTheSameHolderRefreshesTheOneLock(): void
    {
        $resource = $this->aResource();
        $holder = $this->users()[0];

        $first = $this->service()->acquire(
            $resource,
            $holder,
        );
        $second = $this->service()->acquire(
            $resource,
            $holder,
        );

        self::assertInstanceOf(
            EditLock::class,
            $first,
        );
        self::assertInstanceOf(
            EditLock::class,
            $second,
        );
        // The same row is refreshed, never a second lock for the same resource.
        self::assertSame(
            $first->getId(),
            $second->getId(),
        );
    }

    public function testAcquireIsBlockedWhileAnotherHolderIsAlive(): void
    {
        $resource = $this->aResource();
        [
            $holder, $other
        ] = $this->users();

        $this->service()->acquire(
            $resource,
            $holder,
        );
        $blocked = $this->service()->acquire(
            $resource,
            $other,
        );

        self::assertNull($blocked);
        // The original holder keeps the lock.
        $lock = $this->locks()->findOneByResource(
            $resource->getResourceId(),
            (int) $resource->getId(),
        );
        self::assertSame(
            $holder->getLidnr(),
            $lock?->getLockedBy()?->getLidnr(),
        );
    }

    public function testForceAcquireTakesOverAnAliveLock(): void
    {
        $resource = $this->aResource();
        [
            $holder, $reviewer
        ] = $this->users();

        $this->service()->acquire(
            $resource,
            $holder,
        );
        $taken = $this->service()->acquire(
            $resource,
            $reviewer,
            true,
        );

        // The reviewer force-takes the still-alive lock from the original holder.
        self::assertSame(
            $reviewer->getLidnr(),
            $taken?->getLockedBy()?->getLidnr(),
        );
    }

    public function testStaleLockIsTakenOverWithoutForce(): void
    {
        $resource = $this->aResource();
        [
            $holder, $other
        ] = $this->users();

        $lock = $this->service()->acquire(
            $resource,
            $holder,
        );
        self::assertInstanceOf(
            EditLock::class,
            $lock,
        );
        $this->makeStale($lock);

        $taken = $this->service()->acquire(
            $resource,
            $other,
        );

        // An abandoned (un-pinged past the TTL) lock is taken over silently, so an editor who left frees it without a
        // forced take-over.
        self::assertSame(
            $other->getLidnr(),
            $taken?->getLockedBy()?->getLidnr(),
        );
    }

    public function testPingReflectsHolderAndLiveness(): void
    {
        $resource = $this->aResource();
        [
            $holder, $other
        ] = $this->users();
        $lock = $this->service()->acquire(
            $resource,
            $holder,
        );
        self::assertInstanceOf(
            EditLock::class,
            $lock,
        );

        // The live holder's heartbeat succeeds; a non-holder's never does.
        self::assertTrue($this->service()->ping(
            $resource,
            $holder,
        ));
        self::assertFalse($this->service()->ping(
            $resource,
            $other,
        ));

        // Once the lock has gone stale even the holder's heartbeat fails, so the front-end locks the form down.
        $this->makeStale($lock);
        self::assertFalse($this->service()->ping(
            $resource,
            $holder,
        ));
    }

    public function testReleaseRemovesTheLockOnlyForItsHolder(): void
    {
        $resource = $this->aResource();
        [
            $holder, $other
        ] = $this->users();
        $this->service()->acquire(
            $resource,
            $holder,
        );

        // A non-holder cannot drop someone else's lock ...
        $this->service()->release(
            $resource,
            $other,
        );
        self::assertNotNull($this->locks()->findOneByResource(
            $resource->getResourceId(),
            (int) $resource->getId(),
        ));

        // ... but the holder can.
        $this->service()->release(
            $resource,
            $holder,
        );
        self::assertNull($this->locks()->findOneByResource(
            $resource->getResourceId(),
            (int) $resource->getId(),
        ));
    }

    public function testBlockingLockReportsAnotherHoldersAliveLockOnly(): void
    {
        $resource = $this->aResource();
        [
            $holder, $other
        ] = $this->users();
        $lock = $this->service()->acquire(
            $resource,
            $holder,
        );
        self::assertInstanceOf(
            EditLock::class,
            $lock,
        );

        // The holder is not blocked by their own lock ...
        self::assertNull($this->service()->blockingLock(
            $resource,
            $holder,
        ));
        // ... but another principal is.
        self::assertNotNull($this->service()->blockingLock(
            $resource,
            $other,
        ));

        // A stale lock blocks nobody (it is up for grabs).
        $this->makeStale($lock);
        self::assertNull($this->service()->blockingLock(
            $resource,
            $other,
        ));
    }

    public function testPurgeSchedulesRemovalWithoutFlushing(): void
    {
        $resource = $this->aResource();
        $lock = $this->service()->acquire(
            $resource,
            $this->users()[0],
        );
        self::assertInstanceOf(
            EditLock::class,
            $lock,
        );

        $this->service()->purge($resource);

        // The contract a batched aggregate-delete relies on: the lock is scheduled for removal but NOT yet flushed.
        self::assertTrue($this->entityManager->getUnitOfWork()->isScheduledForDelete($lock));

        $this->entityManager->flush();
        self::assertNull($this->locks()->findOneByResource(
            $resource->getResourceId(),
            (int) $resource->getId(),
        ));
    }

    private function service(): EditLockService
    {
        return self::getContainer()->get(EditLockService::class);
    }

    private function locks(): EditLockRepository
    {
        return $this->entityManager->getRepository(EditLock::class);
    }

    private function aResource(): RevisableInterface
    {
        $activity = $this->entityManager->getRepository(Activity::class)->findOneBy([]);
        self::assertInstanceOf(
            Activity::class,
            $activity,
            'The seed is expected to contain at least one activity.',
        );

        return $activity;
    }

    /**
     * Two distinct seeded users to act as competing editors.
     *
     * @return array{User, User}
     */
    private function users(): array
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(
            [],
            ['lidnr' => 'ASC'],
            2,
        );
        self::assertCount(
            2,
            $users,
            'The seed is expected to contain at least two users.',
        );

        return [
            $users[0],
            $users[1],
        ];
    }

    /**
     * Age a lock past the heartbeat TTL on the managed entity (the service reads the in-memory instance, so the stale
     * ping must be set here rather than through a DQL update).
     */
    private function makeStale(EditLock $lock): void
    {
        $lock->setLastPingAt(new DateTime(sprintf(
            '-%d seconds',
            EditLockService::TTL_SECONDS + 30,
        )));
        $this->entityManager->flush();
    }
}
