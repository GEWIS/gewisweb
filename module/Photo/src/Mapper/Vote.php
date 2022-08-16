<?php

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use DateInterval;
use DateTime;
use Photo\Model\Vote as VoteModel;

/**
 * Mappers for Vote.
 */
class Vote extends BaseMapper
{
    /**
     * Get the amount of votes of all photos that have been visited
     * in the specified time range.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return array of array of string
     */
    public function getVotesInRange(
        DateTime $startDate,
        DateTime $endDate,
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('IDENTITY(vote.photo)', 'Count(vote.photo)')
            ->from($this->getRepositoryName(), 'vote')
            ->where('vote.dateTime BETWEEN ?1 AND ?2')
            ->groupBy('vote.photo')
            ->setParameter(1, $startDate)
            ->setParameter(2, $endDate);

        return $qb->getQuery()->getResult();
    }

    /**
     * Check if a vote exists.
     *
     * @param int $photoId The photo
     * @param int $lidnr The tag
     *
     * @return VoteModel|null
     */
    public function findVote(
        int $photoId,
        int $lidnr,
    ): ?VoteModel {
        return $this->getRepository()->findOneBy(
            [
                'photo' => $photoId,
                'voter' => $lidnr,
            ]
        );
    }

    /**
     * Checks if a member has recently voted.
     *
     * @param int $lidnr
     *
     * @return bool
     */
    public function hasRecentVote(int $lidnr): bool
    {
        $nowMinusMonth = (new DateTime('now'))->sub(new DateInterval('P1M'));

        $qb = $this->getRepository()->createQueryBuilder('v');
        $qb->select('v.id')
            ->where('v.voter = :lidnr')
            ->andWhere('v.dateTime > :after')
            ->setParameter('lidnr', $lidnr)
            ->setParameter('after', $nowMinusMonth)
            ->setMaxResults(1);

        return 0 !== count($qb->getQuery()->getResult());
    }

    protected function getRepositoryName(): string
    {
        return VoteModel::class;
    }
}
