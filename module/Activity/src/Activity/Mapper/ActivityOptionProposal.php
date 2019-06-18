<?php
namespace Activity\Mapper;
use Option\Model\ActivityOptionProposal as ActivityOptionProposalModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
class ActivityOptionProposal
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
     * Finds the ActivityOptionProposal model with the given id.
     *
     * @param int $id
     * @return ActivityOptionProposalModel
     */
    public function getActivityOptionProposalById($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('AcitivityOption\Model\ActivityOptionProposal');
    }
}