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
     * Finds the MaxActivityOptions model with the given organ.
     *
     * @param Organ $organ
     * @return MaxActivityOptionsModel
     */
    public function getMaxActivityOptionsByOrgan($organ)
    {
        return $this->getRepository()->find($organ);
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