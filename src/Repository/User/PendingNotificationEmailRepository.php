<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\PendingNotificationEmail;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function array_values;

/**
 * @template-extends ServiceEntityRepository<PendingNotificationEmail>
 */
class PendingNotificationEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PendingNotificationEmail::class,
        );
    }

    /**
     * The distinct members who have at least one notification queued for email. The queue is drained every run, so the
     * backlog stays small enough to resolve the members in memory.
     *
     * @return User[]
     */
    public function findUsersWithPending(): array
    {
        $users = [];
        foreach ($this->findAll() as $pending) {
            $user = $pending->getUser();
            $users[$user->getLidnr()] = $user;
        }

        return array_values($users);
    }

    /**
     * A member's queued notifications, oldest first.
     *
     * @return PendingNotificationEmail[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->join(
                'p.notification',
                'n',
            )
            ->where('p.user = :user')
            ->setParameter(
                'user',
                $user->getLidnr(),
            )
            ->orderBy(
                'n.createdAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }

    public function deleteForUser(User $user): void
    {
        $this->createQueryBuilder('p')
            ->delete()
            ->where('p.user = :user')
            ->setParameter(
                'user',
                $user->getLidnr(),
            )
            ->getQuery()
            ->execute();
    }
}
