<?php


namespace User\Mapper;


use Doctrine\ORM\EntityManager;
use User\Model\NewCompany as NewCompanyModel;
use User\Model\Company as CompanyModel;

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
     * @return \User\Model\Company
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
     * @return CompanyModel
     */
    public function findByLogin($login)
    {
        // create query for user
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from('User\Model\Company', 'c');


        $qb->where('LOWER(c.contactEmail) = ?1');

        // set the parameters
        $qb->setParameter(1, strtolower($login));
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Detach a user from the entity manager.
     *
     * @param CompanyModel $company
     */
    public function detach(CompanyModel $company)
    {
        $this->em->detach($company);
    }

    /**
     * Re-attach a user to the entity manager.
     *
     * @param CompanyModel $company
     *
     * @return CompanyModel
     */
    public function merge(CompanyModel $company)
    {
        return $this->em->merge($company);
    }

    /**
     * Finish user creation.
     *
     * This will both destroy the NewUser and create the given user
     *
     * @param CompanyModel $company User to create
     * @param NewUserModel $newUser NewUser to destroy
     */
    // TODO: comments
    public function createCompany(CompanyModel $company, NewCompanyModel $newCompany)
    {
        $this->em->persist($company);
        $this->em->remove($newCompany);
        $this->em->flush();
    }

    /**
     * Persist a company model.
     *
     * @param CompanyModel $company Company to persist.
     */
    // TODO: comments
    public function persist(CompanyModel $company)
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
        return $this->em->getRepository('User\Model\Company');
    }

}
