<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\Decision\Organ;
use App\Entity\User\Enums\UserRoles;
use App\Service\Application\NotificationPublisher;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

use function sprintf;
use function strval;

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
        private NotificationPublisher $publisher,
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
            $this->tellTheBoard($proposal);

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
            $this->tellTheBoard($proposal);
        } finally {
            $connection->executeQuery(
                'SELECT RELEASE_LOCK(?)',
                [$mutex],
            );
        }
    }

    /**
     * A proposal nobody is told about sits in a queue nobody opens, which is how the old calendar ended up needing a
     * nightly email to the web committee to notice anything at all.
     */
    private function tellTheBoard(ActivityProposal $proposal): void
    {
        $proposalId = $proposal->getId();

        if (null === $proposalId) {
            return;
        }

        $notification = new Notification();
        $notification->setType(NotificationType::ActivityProposalAwaitingDecision);
        $notification->setContext([
            'proposal' => strval($proposalId),
            'proposalName' => $proposal->getName(),
        ]);
        $notification->setRecipient(
            null,
            null,
            UserRoles::Board,
        );

        $this->publisher->publish($notification);
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
