<?php

namespace Photo\Mapper;

use Doctrine\ORM\EntityManager;

/**
 * Mappers for Tags.
 *
 */
class Tag
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Retrieves a tag by id from the database.
     *
     * @param integer $tagId the id of the tag
     *
     * @return \Photo\Model\Tag
     */
    public function getTagById($tagId)
    {
        return $this->getRepository()->find($tagId);
    }

    public function findTag($photoId, $lidnr)
    {
        return $this->getRepository()->findOneBy([
            'photo' => $photoId,
            'member' => $lidnr
        ]);
    }

    public function getTagsByLidnr($lidnr)
    {
        return $this->getRepository()->findBy([
            'member' => $lidnr
        ]);
    }

    /**
     * Returns a recent tag for the member whom has the most tags from a set of members.
     *
     * @param array $members
     *
     * @return \Photo\Model\Tag|null
     */
    public function getMostActiveMemberTag($members)
    {
        $qb = $this->em->createQueryBuilder();

        // Retrieve the lidnr of the member with the most tags
        $qb->select('IDENTITY(t.member), COUNT(t.member) as tag_count')
            ->from('Photo\Model\Tag', 't')
            ->where('t.member IN (?1)')
            ->setParameter(1, $members)
            ->groupBy('t.member')
            ->setMaxResults(1)
            ->orderBy('tag_count', 'DESC');

        $res = $qb->getQuery()->getSingleResult();

        if (empty($res)) {
            return null;
        } else {
            $lidnr = $res[1];

            $qb2 = $this->em->createQueryBuilder();

            // Retrieve the most recent tag of a member
            $qb2->select('t')
                ->from('Photo\Model\Tag', 't')
                ->join('Photo\Model\Photo', 'p', 'WITH', 'p.id = t.photo')
                ->where('t.member = ?1')
                ->setParameter(1, $lidnr)
                ->setMaxResults(1)
                ->orderBy('p.dateTime', 'DESC');

            return $qb2->getQuery()->getSingleResult();
        }

    }

    /**
     * Removes a tag.
     *
     * @param \Photo\Model\Tag $tag
     */
    public function remove($tag)
    {
        $this->em->remove($tag);
    }


    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\Tag');
    }

}
