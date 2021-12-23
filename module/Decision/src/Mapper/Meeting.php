<?php

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use DateInterval;
use DateTime;
use Decision\Model\{
    Meeting as MeetingModel,
    MeetingDocument as MeetingDocumentModel,
};
use Doctrine\ORM\{
    NonUniqueResultException,
    NoResultException,
    Exception\ORMException,
};
use InvalidArgumentException;

class Meeting extends BaseMapper
{
    /**
     * Find all meetings.
     *
     * @param int|null $limit The amount of results, default is all
     *
     * @return array Of all meetings
     */
    public function findAllMeetings(?int $limit = null): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m, COUNT(d)')
            ->from($this->getRepositoryName(), 'm')
            ->leftJoin('m.decisions', 'd')
            ->groupBy('m')
            ->orderBy('m.date', 'DESC');

        if (is_int($limit) && $limit >= 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all meetings which have the given type.
     *
     * @param string $type AV|BV|VV|Virt
     *
     * @return array
     */
    public function findByType(string $type): array
    {
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->where('m.type = :type')
            ->orderBy('m.date', 'DESC')
            ->setParameter(':type', $type);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all meetings that have taken place.
     *
     * @param int|null $limit The amount of results, default is all
     * @param string|null $type
     *
     * @return array Meetings that have taken place
     */
    public function findPast(
        ?int $limit = null,
        ?string $type = null,
    ): array {
        $qb = $this->em->createQueryBuilder();

        // Use yesterday because a meeting might still take place later on the day
        $date = new DateTime();
        $date->add(DateInterval::createFromDateString('yesterday'));

        $qb->select('m, COUNT(d)')
            ->from($this->getRepositoryName(), 'm')
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
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    public function findLatestAV(): ?MeetingModel
    {
        return $this->findFutureMeeting('DESC');
    }

    /**
     * Returns the closest upcoming AV.
     *
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    public function findUpcomingMeeting(): ?MeetingModel
    {
        return $this->findFutureMeeting('ASC', true);
    }

    /**
     * Find a meeting with all decisions.
     *
     * @param string|null $type
     * @param int|null $number
     *
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    public function findMeeting(
        ?string $type,
        ?int $number,
    ): ?MeetingModel {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m, d, db')
            ->from($this->getRepositoryName(), 'm')
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->leftJoin('m.decisions', 'd')
            ->leftJoin('d.destroyedby', 'db')
            ->orderBy('d.point')
            ->addOrderBy('d.number');

        $qb->setParameter(':type', $type);
        $qb->setParameter(':number', $number);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $id
     *
     * @return MeetingDocumentModel|null
     * @throws ORMException
     */
    public function findDocument(int $id): ?MeetingDocumentModel
    {
        return $this->em->find('Decision\Model\MeetingDocument', $id);
    }

    /**
     * Returns the document with the specified ID.
     *
     * @param int $id Document ID
     *
     * @return MeetingDocumentModel
     *
     * @throws InvalidArgumentException If the document does not exist
     * @throws ORMException
     */
    public function findDocumentOrFail(int $id): MeetingDocumentModel
    {
        $document = $this->findDocument($id);

        if (is_null($document)) {
            throw new InvalidArgumentException(sprintf("A document with the provided ID '%d' does not exist.", $id));
        }

        return $document;
    }

    /**
     * Returns the maximum document position for the given meeting.
     *
     * @param MeetingModel $meeting
     *
     * @return int|null NULL if no documents are associated to the meeting
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findMaxDocumentPosition(MeetingModel $meeting): ?int
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('MAX(d.displayPosition)')
            ->from($this->getRepositoryName(), 'm')
            ->join('m.documents', 'd')
            ->where('m.type = :type')
            ->andWhere('m.number = :number');

        $qb->setParameter(':type', $meeting->getType());
        $qb->setParameter(':number', $meeting->getNumber());

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Finds an AV or VV planned in the future.
     *
     * @param string $order Order of the future AV's
     * @param bool $vvs If VV's are included in this
     *
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    private function findFutureMeeting(
        string $order,
        bool $vvs = false,
    ): ?MeetingModel {
        $qb = $this->getRepository()->createQueryBuilder('m');

        $today = new DateTime();
        $maxDate = $today->sub(new DateInterval('P1D'));

        $qb->where('m.type = :type')
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

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return MeetingModel::class;
    }
}
