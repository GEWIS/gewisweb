<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Decision as DecisionModel;
use Decision\Model\Enums\MeetingTypes;
use Override;

use function addcslashes;
use function implode;
use function is_numeric;
use function preg_match;

use const PREG_UNMATCHED_AS_NULL;

/**
 * @template-extends BaseMapper<DecisionModel>
 */
class Decision extends BaseMapper
{
    /**
     * Search decisions.
     *
     * @return DecisionModel[]
     */
    public function search(string $query): array
    {
        $qb = $this->getRepository()->createQueryBuilder('d');
        $qb->select('d, m')
            ->where('d.contentNL LIKE :query')
            ->join('d.meeting', 'm')
            ->orderBy('m.date', 'DESC')
            ->setMaxResults(100);

        $qb->setParameter('query', '%' . addcslashes($query, '%_') . '%');

        // Start by matching meeting type and meeting number, then we also match additional meeting points and decision
        // numbers. Both the Dutch and English abbreviation for the meeting types can be used.
        //
        // To make it usable, we also split the meeting type and meeting number match into two separate capture groups.
        // In total there are four capture groups.
        //
        // Example:
        // BV 123.456.789
        //
        // Result:
        // array(5) {
        //     [0]=> string(14) "BV 123.456.789"
        //     [1]=> string(2) "BV"
        //     [2]=> string(3) "123"
        //     [3]=> string(3) "456"
        //     [4]=> string(3) "789"
        // }
        $meetingRegex = '/(?:(' . implode('|', MeetingTypes::getSearchableStrings()) . ')'
            . ' ([0-9]+))(?:.([0-9]+))?(?:.([0-9]+))?/';
        $meetingInfo = [];
        if (1 === preg_match($meetingRegex, $query, $meetingInfo, PREG_UNMATCHED_AS_NULL)) {
            $meetingType = MeetingTypes::tryFromSearch($meetingInfo[1]);
            $meetingNumber = (int) $meetingInfo[2];

            $where = 'd.meeting_type = :meeting_type AND d.meeting_number = :meeting_number';
            if (null === $meetingInfo[3]) {
                $qb->orWhere($where);
            } elseif (null === $meetingInfo[4]) {
                $qb->orWhere($where . ' AND d.point = :point')
                    ->setParameter('point', (int) $meetingInfo[3]);
            } else {
                $qb->orWhere($where . ' AND d.point = :point AND d.number = :number')
                    ->setParameter('point', (int) $meetingInfo[3])
                    ->setParameter('number', (int) $meetingInfo[4]);
            }

            $qb->setParameter('meeting_type', $meetingType)
                ->setParameter('meeting_number', $meetingNumber);
        } elseif (is_numeric($query)) {
            $qb->orWhere('d.meeting_number = :meeting_number')
                ->setParameter('meeting_number', (int) $query);
        }

        return $qb->getQuery()->getResult();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return DecisionModel::class;
    }
}
