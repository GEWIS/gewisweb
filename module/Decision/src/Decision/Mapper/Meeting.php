<?php

namespace Decision\Mapper;

use Decision\Model\Meeting as MeetingModel;
use Decision\Model\MeetingDocument;
use Doctrine\ORM\EntityManager;

class Meeting
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
     * Find all meetings.
     *
     * @param int|null $limit   The amount of results, default is all
     * @return array Of all meetings
     */
    public function findAll($limit = null)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, COUNT(d)')
            ->from('Decision\Model\Meeting', 'm')
            ->leftJoin('m.decisions', 'd')
            ->groupBy('m')
            ->orderBy('m.date', 'DESC');

        if (is_int($limit) && $limit >= 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all meetings which have the given type
     *
     * @param AV|BV|VV|Virt $type
     *
     * @return array
     */
    public function findByType($type)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from('Decision\Model\Meeting', 'm')
            ->where('m.type = :type')
            ->orderBy('m.date', 'DESC')
            ->setParameter(':type', $type);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all meetings that have taken place
     *
     * @param int|null $limit The amount of results, default is all
     * @return array Meetings that have taken place
     */
    public function findPast($limit = null, $type = null)
    {
        $qb = $this->em->createQueryBuilder();

        // Use yesterday because a meeting might still take place later on the day
        $date = new \DateTime();
        $date->add(\DateInterval::createFromDateString('yesterday'));

        $qb->select('m, COUNT(d)')
            ->from('Decision\Model\Meeting', 'm')
            ->where('m.date <= :date')
            ->leftJoin('m.decisions', 'd')
            ->groupBy('m')
            ->orderBy('m.date', 'DESC')
            ->setParameter('date', $date);

        if (is_int($limit) && $limit >= 0) {
            $qb->setMaxResults($limit);
        }

        if (is_string($type)) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the latest upcoming AV or null if there is none.
     *
     * Note that if multiple AVs are planned, the one that is planned furthest
     * away is returned.
     *
     * @return \Decision\Model\Meeting|null
     */
    public function findLatestAV()
    {
        return $this->findFutureMeeting('DESC');
    }

    /**
     * Returns the closest upcoming AV
     *
     * @return \Decision\Model\Meeting|null
     */
    public function findUpcomingMeeting()
    {
        return $this->findFutureMeeting('ASC', true);
    }

    /**
     * Find a meeting with all decisions.
     *
     * @param string $type
     * @param int $number
     *
     * @return MeetingModel
     */
    public function find($type, $number)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, d, db')
            ->from('Decision\Model\Meeting', 'm')
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->leftJoin('m.decisions', 'd')
            ->leftJoin('d.destroyedby', 'db')
            ->orderBy('d.point')
            ->addOrderBy('d.number');

        $qb->setParameter(':type', $type);
        $qb->setParameter(':number', $number);

        return $qb->getQuery()->getSingleResult();
    }

    public function findDocument($id)
    {
        return $this->em->find('Decision\Model\MeetingDocument', $id);
    }

    /**
     * Persist a meeting model.
     *
     * @param MeetingModel $meeting Meeting to persist.
     */
    public function persist(MeetingModel $meeting)
    {
        $this->em->persist($meeting);
        $this->em->flush();
    }

    /**
     * Persist a document model.
     *
     * @param MeetingDocument $document Document to persist.
     */
    public function persistDocument(MeetingDocument $document)
    {
        $this->em->persist($document);
        $this->em->flush();
    }

    /**
     * Removes an entity.
     *
     * @param $entity
     */
    public function remove($entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Meeting');
    }

    /**
     * Finds an AV or VV planned in the future
     *
     * @param string $order Order of the future AV's
     * @param bool $vvs If VV's are included in this
     * @return \Decision\Model\Meeting|null
     */
    private function findFutureMeeting($order, $vvs = false)
    {
        $qb = $this->em->createQueryBuilder();

        $today = new \DateTime();
        $maxDate = $today->sub(new \DateInterval('P1D'));

        $qb->select('m')
            ->from('Decision\Model\Meeting', 'm')
            ->where('m.type = :type')
            ->where('m.date >= :date')
            ->orderBy('m.date', $order)
            ->setParameter('date', $maxDate)
            ->setMaxResults(1);

        if ($vvs) {
            $qb->andWhere("m.type = 'AV' OR m.type = 'VV'");
            return $qb->getQuery()->getOneOrNullResult();
        }

        $qb->andWhere("m.type = 'AV'");
        return $qb->getQuery()->getOneOrNullResult();
    }
}
