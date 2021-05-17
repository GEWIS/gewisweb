<?php

namespace User\Mapper;

use User\Model\NewCompany as NewCompanyModel;
use Doctrine\ORM\EntityManager;

class NewCompany
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
     * Get the new user by code.
     *
     * @param string $code
     *
     * @return NewCompanyModel
     */
    public function getByCode($code)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('nc')
            ->from('User\Model\NewCompany', 'nc')
            ->where('nc.code = ?1');
        $qb->setParameter(1, $code);
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Find a company by its email.
     *
     * @param string $contactEmail company email
     *
     * @return \Company\Model\Company
     */
    public function findByEmail($contactEmail)
    {
        return $this->getRepository()->findOneBy(['contactEmail' => $contactEmail]);
    }


    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\NewCompany');
    }

    /**
     * Delete the existing activation code for a company
     *
     * @param string $company
     * @return array
     */
    public function deleteByCompany($company)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete('User\Model\NewCompany', 'u');
        $qb->where('u.contactEmail = :com');
        $qb->setParameter('com', $company);
        return $qb->getQuery()->getResult();
    }

    public function persist(NewCompanyModel $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}
