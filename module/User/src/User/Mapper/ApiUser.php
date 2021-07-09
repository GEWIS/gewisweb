<?php

namespace User\Mapper;

use Doctrine\ORM\EntityRepository;
use User\Model\ApiUser as ApiUserModel;
use Doctrine\ORM\EntityManager;

class ApiUser
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
     * Find an API user by it's token
     *
     * @param string $token Token of the user
     *
     * @return ApiUserModel
     */
    public function findByToken($token)
    {
        return $this->getRepository()->findOneBy(['token' => $token]);
    }

    /**
     * Find all tokens.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find a token by it's ID.
     *
     * @param int $id
     *
     * @return ApiUserModel
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Remove a token by it's ID.
     *
     * @param int $id
     */
    public function remove($id)
    {
        $apiUser = $this->find($id);
        $this->em->remove($apiUser);
        $this->em->flush();
    }

    /**
     * Persist an API user model.
     *
     * @param ApiUserModel $apiUser ApiUser to persist.
     */
    public function persist(ApiUserModel $apiUser)
    {
        $this->em->persist($apiUser);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\ApiUser');
    }
}
