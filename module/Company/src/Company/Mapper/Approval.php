<?php


namespace Company\Mapper;

use Company\Model\ApprovalModel\ApprovalPending;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class Approval
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

    public function persist($job)
    {
        $this->em->persist($job);
        $this->em->flush();
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Saves all modified entities that are marked persistant
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Find all pending approvals
     *
     * @return array ApprovalPending model
     */
    public function findPendingApprovals()
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\ApprovalModel\ApprovalPending', 'ap');

        $select = $builder->generateSelectClause(['ap' => 't1']);
        $sql = "SELECT $select FROM ApprovalPending AS t1";
        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }


    /**
     * Find the company with the given slugName.
     *
     * @param slugName The 'username' of the company to get.
     * @param asObject if yes, returns the company as an object in an array, otherwise returns the company as an array of an array
     *
     * @return An array of companies with the given slugName.
     */
    public function findEditableCompaniesBySlugName($slugName, $asObject)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.slugName=:slugCompanyName');
        $qb->setParameter('slugCompanyName', $slugName);
        $qb->setMaxResults(1);
        if ($asObject) {
            return $qb->getQuery()->getResult();
        }

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\Approval');
    }

}
