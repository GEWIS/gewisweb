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
        return $this->getRepository()->findOneBy(array(
            'photo' => $photoId,
            'member' => $lidnr
        ));
    }

    public function getTagsByLidnr($lidnr)
    {
        return $this->getRepository()->findBy(array(
            'member' => $lidnr
        ));
    }

    /**
     * Returns the most recent photo of a give member. Returns null if there are none
     *
     * @param \Decision\Model\Member $member
     *
     * @return \Photo\Model\Tag|null
     */
    public function getRandomMemberTag($member)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('t')
            ->from('Photo\Model\Tag', 't')
            ->where('t.member = ?1')
            ->setMaxResults(1)
            ->setParameter(1, $member)
            ->addSelect('RAND() as HIDDEN rand')
            ->orderBy('rand');

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
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
