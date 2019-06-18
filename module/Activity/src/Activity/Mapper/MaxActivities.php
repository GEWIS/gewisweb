<?php
namespace Activity\Mapper;
use Option\Model\MaxActivityOptions as MaxActivityOptionsModel;
use Decision\Model\Organ;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
class MaxActivities
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    /**
     * Finds the MaxActivityOptions model with the given id.
     *
     * @param int $id
     * @return MaxActivityOptionsModel
     */
    public function getMaxActivityOptionsById($id)
    {
        return $this->getRepository()->find($id);
    }
    /**
     * Finds the MaxActivityOptions model with the given organ and period
     *
     * @param int $organ_id
     * @param int $period_id
     * @return MaxActivityOptionsModel
     */
    public function getMaxActivityOptionsByOrganPeriod($organ_id, $period_id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('x')
            ->from('AcitivityOption\Model\ActivityOptionCreationPeriod', 'x')
            ->where('x.organ = :organ_')
            ->where('x.period = :period')
            ->setParameter('organ', $organ_id)
            ->setParameter('period', $period_id)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('AcitivityOption\Model\MaxActivities');
    }
}