<?php

namespace Photo\Mapper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use Photo\Model\ProfilePhoto as ProfilePhotoModel;

/**
 * Mappers for ProfilePhoto.
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
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Checks if the specified photo exists in the database already and returns
     * it if it does.
     *
     * @param int $lidnr The Id of the user to which the photo is assigned
     *
     * @return ProfilePhotoModel|null
     *
     * @throws Exception
     */
    public function getProfilePhotoByLidnr($lidnr)
    {
        $profilePhoto = $this->getRepository()->findOneBy(
            [
                'member' => $lidnr,
            ]
        );

        return $profilePhoto;
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\ProfilePhoto');
    }

    /**
     * Removes a photo.
     */
    public function remove(ProfilePhotoModel $profilePhoto)
    {
        $this->em->remove($profilePhoto);
    }

    /**
     * Persist photo.
     */
    public function persist(ProfilePhotoModel $profilePhoto)
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
     * @return Connection
     */
    public function getConnection()
    {
        return $this->em->getConnection();
    }
}
