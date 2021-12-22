<?php

namespace Photo\Mapper;

use Application\Mapper\BaseMapper;
use Photo\Model\{
    Photo as PhotoModel,
    Tag as TagModel,
};

/**
 * Mappers for Tags.
 */
class Tag extends BaseMapper
{
    /**
     * @param int $photoId
     * @param int $lidnr
     *
     * @return TagModel|null
     */
    public function findTag(
        int $photoId,
        int $lidnr,
    ): ?TagModel {
        return $this->getRepository()->findOneBy(
            [
                'photo' => $photoId,
                'member' => $lidnr,
            ]
        );
    }

    /**
     * @param int $lidnr
     * @return array
     */
    public function getTagsByLidnr(int $lidnr): array
    {
        return $this->getRepository()->findBy(
            [
                'member' => $lidnr,
            ]
        );
    }

    /**
     * Returns a recent tag for the member whom has the most tags from a set of members.
     *
     * @param array $members
     *
     * @return TagModel|null
     */
    public function getMostActiveMemberTag(array $members): ?TagModel
    {
        $qb = $this->em->createQueryBuilder();

        // Retrieve the lidnr of the member with the most tags
        $qb->select('IDENTITY(t.member), COUNT(t.member) as tag_count')
            ->from($this->getRepositoryName(), 't')
            ->where('t.member IN (?1)')
            ->setParameter(1, $members)
            ->groupBy('t.member')
            ->setMaxResults(1)
            ->orderBy('tag_count', 'DESC');

        $res = $qb->getQuery()->getResult();

        if (empty($res)) {
            return null;
        }

        $lidnr = $res[0][1];

        // Retrieve the most recent tag of a member
        $qb2 = $this->getRepository()->createQueryBuilder('t');
        $qb2->join(PhotoModel::class, 'p', 'WITH', 'p.id = t.photo')
            ->where('t.member = ?1')
            ->setParameter(1, $lidnr)
            ->setMaxResults(1)
            ->orderBy('p.dateTime', 'DESC');

        $res = $qb2->getQuery()->getResult();

        if (empty($res)) {
            return null;
        }

        return $res[0];
    }

    protected function getRepositoryName(): string
    {
        return TagModel::class;
    }
}
