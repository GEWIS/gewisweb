<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use DateInterval;
use DateTime;
use Decision\Model\Enums\MeetingTypes;
use Decision\Model\Meeting as MeetingModel;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Override;

use function is_int;

/**
 * @template-extends BaseMapper<MeetingModel>
 * @psalm-type MeetingArrayType = array<array-key, array{
 *     0: MeetingModel,
 *     1: int,
 * }>
 */
class Meeting extends BaseMapper
{
    /**
     * Find all meetings.
     *
     * @param int|null $limit The amount of results, default is all
     *
     * @return MeetingArrayType
     */
    public function findAllMeetings(
        ?int $limit = null,
        ?MeetingTypes $type = null,
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('m, COUNT(d)')
            ->from($this->getRepositoryName(), 'm')
            ->leftJoin('m.decisions', 'd')
            ->groupBy('m')
            ->orderBy('m.date', 'DESC');

        if (is_int($limit) && $limit >= 0) {
            $qb->setMaxResults($limit);
        }

        if (null !== $type) {
            $qb->andWhere('m.type = :type')
                ->setParameter(':type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all meetings which have the given type.
     *
     * @param MeetingTypes $type ALV|BV|VV|Virt
     *
     * @return MeetingModel[]
     */
    public function findByType(MeetingTypes $type): array
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
     * @param int $limit The amount of results
     *
     * @return MeetingModel[] Meetings that have taken place
     */
    public function findPast(
        int $limit,
        MeetingTypes $type,
    ): array {
        // Use yesterday because a meeting might still take place later on the day
        $date = new DateTime();
        $date->add(DateInterval::createFromDateString('yesterday'));

        $qb = $this->getRepository()->createQueryBuilder('m')
            ->where('m.date <= :date')
            ->andWhere('m.type = :type')
            ->orderBy('m.date', 'DESC')
            ->setParameter('date', $date)
            ->setParameter('type', $type)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a meeting with all decisions.
     *
     * @throws NonUniqueResultException
     */
    public function findMeeting(
        MeetingTypes $type,
        int $number,
    ): ?MeetingModel {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('m, d, db')
            ->from($this->getRepositoryName(), 'm')
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->leftJoin('m.decisions', 'd')
            ->leftJoin('d.annulledBy', 'db')
            ->orderBy('d.point')
            ->addOrderBy('d.number');

        $qb->setParameter(':type', $type);
        $qb->setParameter(':number', $number);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns the maximum document position for the given meeting.
     *
     * @return int|null NULL if no documents are associated to the meeting
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findMaxDocumentPosition(MeetingModel $meeting): ?int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('MAX(d.displayPosition)')
            ->from($this->getRepositoryName(), 'm')
            ->join('m.documents', 'd')
            ->where('m.type = :type')
            ->andWhere('m.number = :number');

        $qb->setParameter(':type', $meeting->getType());
        $qb->setParameter(':number', $meeting->getNumber());

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the latest upcoming ALV or null if there is none.
     *
     * Note that if multiple ALVs are planned, the one that is planned furthest
     * away is returned.
     *
     * @throws NonUniqueResultException
     */
    public function findLatestALV(): ?MeetingModel
    {
        $qb = $this->getRepository()->createQueryBuilder('m');

        $today = new DateTime();
        $maxDate = $today->sub(new DateInterval('P1D'));

        $qb->where('m.type = :gmm')
            ->andWhere('m.date >= :date')
            ->orderBy('m.date', 'DESC')
            ->setParameter('gmm', MeetingTypes::ALV)
            ->setParameter('date', $maxDate)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return MeetingModel[]
     */
    public function findUpcomingAnnouncedMeetings(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('m');

        $today = new DateTime();
        $maxDate = $today->sub(new DateInterval('P1D'));

        $qb->where('m.type = :gmm OR m.type = :cm')
            ->andWhere('m.date >= :date')
            ->orderBy('m.date', 'ASC');

        $qb->setParameter('gmm', MeetingTypes::ALV)
            ->setParameter('cm', MeetingTypes::VV)
            ->setParameter('date', $maxDate);

        return $qb->getQuery()->getResult();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return MeetingModel::class;
    }
}
