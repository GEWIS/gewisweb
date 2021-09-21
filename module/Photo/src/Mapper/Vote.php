<?php

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
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
     * @return array of array of string
     */
    public function getVotesInRange($startDate, $endDate)
    {
        $qb = $this->em->createQueryBuilder();

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
     * @return object|null
     */
    public function findVote($photoId, $lidnr)
    {
        return $this->getRepository()->findOneBy(
            [
                'photo' => $photoId,
                'voter' => $lidnr,
            ]
        );
    }

    protected function getRepositoryName(): string
    {
        return VoteModel::class;
    }
}
