<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\Session;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 *
 * NOTE: All queries that list, count, or delete sessions for a user MUST
 * also filter by firewallName. This guarantees that:
 *   - A "terminate all" action on the `main` firewall does not touch `company` sessions.
 *   - The per-user session cap is enforced independently per firewall.
 *   - The session management UI shows only the sessions relevant to the current user context.
 *
 * The only exception is findOneBySeries(), which is looked up globally (series
 * values are globally unique) – the firewall ownership is then verified
 * by the handler after the lookup.
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Session::class,
        );
    }

    public function findOneBySeries(string $series): ?Session
    {
        return $this->findOneBy(['series' => $series]);
    }

    public function findOneByPhpSessionId(string $phpSessionId): ?Session
    {
        return $this->findOneBy(['phpSessionId' => $phpSessionId]);
    }

    /** @return Session[] */
    public function findActiveByUserOnFirewall(
        string $userIdentifier,
        string $firewallName,
    ): array {
        return $this->createQueryBuilder('s')
            ->where('s.userIdentifier = :uid')
            ->andWhere('s.firewallName = :fw')
            ->andWhere('s.expiresAt > :now')
            ->setParameter(
                'uid',
                $userIdentifier,
            )
            ->setParameter(
                'fw',
                $firewallName,
            )
            ->setParameter(
                'now',
                new DateTimeImmutable(),
            )
            ->orderBy(
                's.lastUsedAt',
                'DESC',
            )
            ->getQuery()
            ->getResult();
    }

    /** @return Session[] */
    public function findAllByUserOnFirewall(
        string $userIdentifier,
        string $firewallName,
    ): array {
        return $this->createQueryBuilder('s')
            ->where('s.userIdentifier = :uid')
            ->andWhere('s.firewallName = :fw')
            ->setParameter(
                'uid',
                $userIdentifier,
            )
            ->setParameter(
                'fw',
                $firewallName,
            )
            ->orderBy(
                's.lastUsedAt',
                'DESC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Every session (active or expired) recorded for a user identifier, used to include them in a data export.
     *
     * @return Session[]
     */
    public function findAllByUser(string $userIdentifier): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.userIdentifier = :uid')
            ->setParameter(
                'uid',
                $userIdentifier,
            )
            ->orderBy(
                's.lastUsedAt',
                'DESC',
            )
            ->getQuery()
            ->getResult();
    }

    public function deleteAllForUserOnFirewall(
        string $userIdentifier,
        string $firewallName,
    ): int {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.userIdentifier = :uid')
            ->andWhere('s.firewallName = :fw')
            ->setParameter(
                'uid',
                $userIdentifier,
            )
            ->setParameter(
                'fw',
                $firewallName,
            )
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expiresAt <= :now')
            ->setParameter(
                'now',
                new DateTimeImmutable(),
            )
            ->getQuery()
            ->execute();
    }
}
