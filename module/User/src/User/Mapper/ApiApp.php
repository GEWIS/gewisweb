<?php


namespace User\Mapper;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use User\Model\ApiApp as ApiAppModel;

class ApiApp
{

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param string $appId
     * @return ApiAppModel
     */
    public function findByAppId($appId)
    {
        return $this->getRepository()->findOneBy([
            'appId' => $appId
        ]);
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(ApiAppModel::class);
    }
}