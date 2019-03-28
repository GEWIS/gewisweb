<?php

namespace Photo\Mapper;

use Photo\Model\ProfilePhoto as ProfilePhotoModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for ProfilePhoto.
 *
 */
class ProfilePhoto
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
     * Checks if the specified photo exists in the database already and returns
     * it if it does.
     *
     * @param int   $lidnr     The Id of the user to which the photo is assigned
     *
     * @return \Photo\Model\ProfilePhoto|null
     */
    public function getProfilePhotoByLidnr($lidnr)
    {
        return $this->getRepository()->findOneBy([
            'member' => $lidnr
        ]);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\ProfilePhoto');
    }

    /**
     * Removes a photo
     *
     * @param \Photo\Model\ProfilePhoto $profilePhoto
     */
    public function remove(PhotoModel $profilePhoto)
    {
        $this->em->remove($profilePhoto);
    }

    /**
     * Persist photo
     *
     * @param \Photo\Model\ProfilePhoto $profilePhoto
     */
    public function persist(PhotoModel $profilePhoto)
    {
        $this->em->persist($profilePhoto);
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Get the entity manager connection.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->em->getConnection();
    }

}
