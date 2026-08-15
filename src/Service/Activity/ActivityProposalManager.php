<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Decision\Organ;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

use function sprintf;

/**
 * The single home for writing an activity proposal, so the allowance is checked once more where it actually matters.
 *
 * The form already refuses a proposal a body has no room for, but counting and then inserting is two steps: two people
 * from one body pressing submit at the same moment both count the same number and both get through. This re-counts and
 * inserts while holding a MariaDB named lock on the body and period, the same mutex arrangement
 * {@see \App\Service\Application\EditLockService} uses, so the last slot goes to exactly one of them.
 *
 * There is no unique index that could do this instead: an allowance is a count, not a value, and MariaDB has nothing
 * to enforce a count with.
 *
 * Whether the period is open, whether the user may act for the body, and what the proposal says are the callers' and
 * the form's business; this service only guards the one thing they cannot.
 */
final readonly class ActivityProposalManager
{
    private const int ACQUIRE_TIMEOUT_SECONDS = 5;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProposalLimitResolver $limitResolver,
    ) {
    }

    /**
     * Store a proposal, refusing it when the body ran out of room while the form was being filled in.
     *
     * @throws ProposalAllowanceExhausted when somebody else took the last slot in the meantime.
     */
    public function create(ActivityProposal $proposal): void
    {
        $organ = $proposal->getOrgan();

        // The board hosts its own activities and is held to no allowance, so there is nothing to serialise against.
        if (null === $organ) {
            $this->entityManager->persist($proposal);
            $this->entityManager->flush();

            return;
        }

        $mutex = $this->mutexFor(
            $organ,
            $proposal,
        );
        $connection = $this->entityManager->getConnection();

        $granted = $connection->executeQuery(
            'SELECT GET_LOCK(?, ?)',
            [
                $mutex,
                self::ACQUIRE_TIMEOUT_SECONDS,
            ],
        )->fetchOne();

        // GET_LOCK() answers 1 when granted, 0 when the wait timed out and NULL on error. Never fall through
        // unguarded: without the mutex the re-count below is exactly the race it exists to close.
        if (null === $granted) {
            throw new RuntimeException(sprintf(
                'Failed to obtain the proposal mutex %s.',
                $mutex,
            ));
        }

        if (1 !== (int) $granted) {
            throw new ProposalAllowanceExhausted(
                'Somebody else in this body is handing a proposal in at this moment. Please try again.',
            );
        }

        try {
            if (
                $this->limitResolver->allowanceFor(
                    $organ,
                    $proposal->getPeriod(),
                )->isExhausted()
            ) {
                throw new ProposalAllowanceExhausted(
                    'This body has no room left for another proposal in this period.',
                );
            }

            $this->entityManager->persist($proposal);
            $this->entityManager->flush();
        } finally {
            $connection->executeQuery(
                'SELECT RELEASE_LOCK(?)',
                [$mutex],
            );
        }
    }

    private function mutexFor(
        Organ $organ,
        ActivityProposal $proposal,
    ): string {
        return sprintf(
            'activity_proposal_%d_%d',
            $proposal->getPeriod()->getId() ?? 0,
            $organ->getId() ?? 0,
        );
    }
}
