<?php
namespace Activity\Mapper;
use Option\Model\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodModel;
use DateTime;
use Decision\Model\Organ;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
class ActivityOptionCreationPeriod
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
     * Finds the ActivityOptionCreationPeriod model with the given id.
     *
     * @param int $id
     * @return ActivityOptionCreationPeriodModel
     */
    public function getActivityOptionCreationPeriodById($id)
    {
        return $this->getRepository()->find($id);
    }
    /**
     * Finds the ActivityOptionCreationPeriod model that is currently active
     *
     * @return ActivityOptionCreationPeriod
     * @throws Exception
     */
    public function getCurrentActivityOptionCreationPeriod()
    {
        $qb = $this->em->createQueryBuilder();
        $today = new DateTime();
        $qb->select('x')
            ->from('AcitivityOption\Model\ActivityOptionCreationPeriod', 'x')
            ->where('x.beginPlanningTime < :today')
            ->where('x.endPlanningTime > :today')
            ->orderBy('x.beginTime', 'ASC')
            ->setParameter('today', $today)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }
    /**
     * Finds the ActivityOptionCreationPeriod model that will be active next
     *
     * @return ActivityOptionCreationPeriod
     * @throws Exception
     */
    public function getUpcomingActivityOptionCreationPeriod()
    {
        $qb = $this->em->createQueryBuilder();
        $today = new DateTime();
        $qb->select('x')
            ->from('AcitivityOption\Model\ActivityOptionCreationPeriod', 'x')
            ->where('x.beginPlanningTime > :today')
            ->orderBy('x.beginPlanningTime', 'ASC')
            ->setParameter('today', $today)
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
        return $this->em->getRepository('AcitivityOption\Model\ActivityOptionCreationPeriod');
    }
}