<?php


namespace User\Mapper;


use Doctrine\ORM\EntityManager;
use User\Model\CompanyUser;
use User\Model\NewCompany as NewCompanyModel;
use Company\Model\Company as CompanyModel;

class Company extends Mapper
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
     * Find a company by its id.
     *
     * @param int $id company id
     *
     * @return \User\Model\CompanyUser
     */
    public function findById($id)
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Find a user by its login.
     *
     * @param string $login
     *
     * @return CompanyUser
     */
    public function findByLogin($login)
    {
        // create query for user
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from('User\Model\CompanyUser', 'c');


        $qb->where('LOWER(c.contactEmail) = ?1');

        // set the parameters
        $qb->setParameter(1, strtolower($login));
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
     * Detach a user from the entity manager.
     *
     * @param CompanyUser $company
     */
    public function detach(CompanyUser $company)
    {
        $this->em->detach($company);
    }

    /**
     * Re-attach a user to the entity manager.
     *
     * @param CompanyUser $company
     *
     * @return CompanyUser
     */
    public function merge(CompanyUser $company)
    {
        return $this->em->merge($company);
    }

    /**
     * Finish user creation.
     *
     * This will both destroy the NewUser and create the given user
     *
     * @param CompanyUser $company User to create
     * @param NewUserModel $newUser NewUser to destroy
     */
    // TODO: comments
    public function createCompany(CompanyUser $company, NewCompanyModel $newCompany)
    {
        $this->em->persist($company);
        $this->em->remove($newCompany);
        $this->em->flush();
    }

    /**
     * Persist a company model.
     *
     * @param CompanyUser $company Company to persist.
     */
    // TODO: comments
    public function persist(CompanyUser $company)
    {
        $this->em->persist($company);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\Company');
    }

}
