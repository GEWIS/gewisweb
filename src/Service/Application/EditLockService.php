<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\EditLock;
use App\Entity\Application\RevisableInterface;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Repository\Application\EditLockRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

use function sprintf;

/**
 * Lifecycle of the exclusive {@see EditLock} on a revisable aggregate, kept alive by heartbeat pings: a lock whose last
 * ping is older than {@see self::TTL_SECONDS} is expired and may be taken over silently, so an abandoned editor frees
 * it without a cron. Reviewers (board) may force-take a lock that is still alive. Keyed by the aggregate's resource id
 * + primary key, so one service covers activities, vacancies and companies alike.
 */
final readonly class EditLockService
{
    /**
     * A lock is "alive" while it has been pinged within this many seconds; the front-end pings well inside this window.
     */
    public const int TTL_SECONDS = 90;

    /**
     * How long {@see self::acquire()} waits on the per-resource serialisation lock before giving up (seconds).
     */
    public const int ACQUIRE_TIMEOUT_SECONDS = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EditLockRepository $editLockRepository,
    ) {
    }

    /**
     * Take or refresh the lock for the principal. Returns the held lock on success, or null when it is held and still
     * alive by someone else and $force is false (force is the reviewer take-over path).
     */
    public function acquire(
        RevisableInterface $resource,
        User|CompanyUser $principal,
        bool $force = false,
    ): ?EditLock {
        // Serialise concurrent acquisitions of the same resource at the DB level. Otherwise the find-then-insert /
        // take-over below is a TOCTOU race: two first acquirers both INSERT (the loser's flush 500s on the unique
        // index), or two take-overs both win. A MariaDB named lock keeps it atomic without a failed flush closing the
        // EntityManager; the UNIQUE(resourceId, resourceKey) index stays the integrity backstop.
        $connection = $this->entityManager->getConnection();
        $mutex = sprintf(
            'edit_lock_%s_%d',
            $resource->getResourceId(),
            $this->key($resource),
        );
        $connection->executeQuery(
            'SELECT GET_LOCK(?, ?)',
            [
                $mutex,
                self::ACQUIRE_TIMEOUT_SECONDS,
            ],
        );

        try {
            $lock = $this->find($resource);

            if (null === $lock) {
                $lock = new EditLock();
                $lock->setResourceId($resource->getResourceId());
                $lock->setResourceKey($this->key($resource));
                $lock->setAcquiredAt(new DateTime());
                $this->entityManager->persist($lock);
            } elseif (
                !$this->heldBy(
                    $lock,
                    $principal,
                )
            ) {
                // Held by someone else: only take over if stale or on a forced (reviewer) take-over.
                if (
                    !$force
                    && $this->isAlive($lock)
                ) {
                    return null;
                }

                $lock->setAcquiredAt(new DateTime());
            }

            $this->assign(
                $lock,
                $principal,
            );
            $lock->setLastPingAt(new DateTime());
            $this->entityManager->flush();

            return $lock;
        } finally {
            $connection->executeQuery(
                'SELECT RELEASE_LOCK(?)',
                [$mutex],
            );
        }
    }

    /**
     * Refresh the heartbeat. Returns false when the principal no longer holds the lock (expired or taken over), so the
     * front-end can lock down the form.
     */
    public function ping(
        RevisableInterface $resource,
        User|CompanyUser $principal,
    ): bool {
        $lock = $this->find($resource);
        if (
            null === $lock
            || !$this->heldBy(
                $lock,
                $principal,
            )
            || !$this->isAlive($lock)
        ) {
            return false;
        }

        $lock->setLastPingAt(new DateTime());
        $this->entityManager->flush();

        return true;
    }

    /**
     * Release the lock if the principal holds it (a no-op otherwise, so a stale/taken-over caller cannot drop someone
     * else's lock).
     */
    public function release(
        RevisableInterface $resource,
        User|CompanyUser $principal,
    ): void {
        $lock = $this->find($resource);
        if (
            null === $lock
            || !$this->heldBy(
                $lock,
                $principal,
            )
        ) {
            return;
        }

        $this->entityManager->remove($lock);
        $this->entityManager->flush();
    }

    /**
     * Drop any lock on a resource when the aggregate itself is hard-deleted (an {@see EditLock} has no foreign key to
     * the resource, so the database cannot cascade-remove it).
     *
     * NOTE: The removal is scheduled but NOT flushed, so a batched delete commits it alongside the aggregate.
     */
    public function purge(RevisableInterface $resource): void
    {
        $lock = $this->find($resource);
        if (null === $lock) {
            return;
        }

        $this->entityManager->remove($lock);
    }

    /**
     * The lock that currently blocks the principal from editing (held and alive by someone else), or null when the
     * principal is free to acquire it.
     */
    public function blockingLock(
        RevisableInterface $resource,
        User|CompanyUser $principal,
    ): ?EditLock {
        $lock = $this->find($resource);
        if (
            null === $lock
            || $this->heldBy(
                $lock,
                $principal,
            )
            || !$this->isAlive($lock)
        ) {
            return null;
        }

        return $lock;
    }

    public function isAlive(EditLock $lock): bool
    {
        return $lock->getLastPingAt() > new DateTime(sprintf('-%d seconds', self::TTL_SECONDS));
    }

    private function find(RevisableInterface $resource): ?EditLock
    {
        return $this->editLockRepository->findOneByResource(
            $resource->getResourceId(),
            $this->key($resource),
        );
    }

    /**
     * The aggregate's primary key. A lock is only ever taken on a persisted aggregate, so the id is never null here.
     */
    private function key(RevisableInterface $resource): int
    {
        $id = $resource->getId();
        if (null === $id) {
            throw new LogicException('Cannot take an edit lock on an unpersisted aggregate.');
        }

        return $id;
    }

    private function heldBy(
        EditLock $lock,
        User|CompanyUser $principal,
    ): bool {
        if ($principal instanceof User) {
            $holder = $lock->getLockedBy();

            return null !== $holder
                && $holder->getLidnr() === $principal->getLidnr();
        }

        $holder = $lock->getLockedByCompanyUser();

        return null !== $holder
            && $holder->getId() === $principal->getId();
    }

    private function assign(
        EditLock $lock,
        User|CompanyUser $principal,
    ): void {
        if ($principal instanceof User) {
            $lock->setLockedBy($principal);
            $lock->setLockedByCompanyUser(null);

            return;
        }

        $lock->setLockedByCompanyUser($principal);
        $lock->setLockedBy(null);
    }
}
