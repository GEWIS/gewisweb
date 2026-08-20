<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ProposalLimit;
use App\Entity\Decision\Organ;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProposalLimit>
 */
class ProposalLimitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ProposalLimit::class,
        );
    }

    public function findForOrgan(Organ $organ): ?ProposalLimit
    {
        return $this->findOneBy(['organ' => $organ]);
    }

    /**
     * The standing limits for a set of bodies at once, keyed by body, so listing every body somebody may act for does
     * not cost one query per body.
     *
     * @param Organ[] $organs
     *
     * @return array<int, ProposalLimit>
     */
    public function findForOrgans(array $organs): array
    {
        if ([] === $organs) {
            return [];
        }

        $limits = $this->createQueryBuilder('l')
            ->where('l.organ IN (:organs)')
            ->setParameter(
                'organs',
                $organs,
            )
            ->getQuery()
            ->getResult();

        $byOrgan = [];
        foreach ($limits as $limit) {
            $organId = $limit->getOrgan()->getId();

            if (null === $organId) {
                continue;
            }

            $byOrgan[$organId] = $limit;
        }

        return $byOrgan;
    }

    /**
     * Every standing exception the board has written down, for the screen that manages them. There is no row for a
     * body on the usual number, so this is a short list of exceptions rather than a roll call.
     *
     * @return ProposalLimit[]
     */
    public function findAllWithOrgan(): array
    {
        return $this->createQueryBuilder('l')
            ->select(
                'l',
                'o',
            )
            ->join(
                'l.organ',
                'o',
            )
            ->orderBy(
                'o.abbr',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
