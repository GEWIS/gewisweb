<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Service\Decision\DecisionSearchQuery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function implode;
use function is_numeric;
use function preg_match;
use function sprintf;

use const PREG_UNMATCHED_AS_NULL;

/**
 * @extends ServiceEntityRepository<Decision>
 */
class DecisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Decision::class,
        );
    }

    /**
     * Search decisions: every included term must appear in the Dutch or English text, no excluded term may, and an
     * optional meeting type narrows the text matches. Alongside the text match, the prompt is checked for a meeting
     * reference such as "BV 123.4.5", which matches those decisions directly.
     *
     * @return Decision[]
     */
    public function search(DecisionSearchQuery $search): array
    {
        if ($search->isEmpty()) {
            return [];
        }

        $qb = $this->createQueryBuilder('d');
        $qb->addSelect('m, meetingMinutes, localDetails, decisionMinutes')
            ->join(
                'd.meeting',
                'm',
            )
            ->leftJoin(
                'm.meetingMinutes',
                'meetingMinutes',
            )
            ->leftJoin(
                'm.localDetails',
                'localDetails',
            )
            ->leftJoin(
                'm.minutes',
                'decisionMinutes',
            )
            ->orderBy(
                'm.date',
                'DESC',
            )
            ->setMaxResults(100);

        $conditions = [];

        $textParts = [];
        foreach ($search->includeTerms as $index => $term) {
            $textParts[] = sprintf(
                '(d.contentNL LIKE :include%1$d OR d.contentEN LIKE :include%1$d)',
                $index,
            );
            $qb->setParameter(
                'include' . $index,
                '%' . addcslashes(
                    $term,
                    '%_',
                ) . '%',
            );
        }

        foreach ($search->excludeTerms as $index => $term) {
            $textParts[] = sprintf(
                '(d.contentNL NOT LIKE :exclude%1$d AND d.contentEN NOT LIKE :exclude%1$d)',
                $index,
            );
            $qb->setParameter(
                'exclude' . $index,
                '%' . addcslashes(
                    $term,
                    '%_',
                ) . '%',
            );
        }

        if (null !== $search->type) {
            $textParts[] = 'm.type = :searchType';
            $qb->setParameter(
                'searchType',
                $search->type->value,
            );
        }

        if ([] !== $textParts) {
            $conditions[] = '(' . implode(
                ' AND ',
                $textParts,
            ) . ')';
        }

        $reference = $this->referenceCondition(
            $qb,
            $search->remainder,
        );
        if (null !== $reference) {
            $conditions[] = $reference;
        }

        if ([] === $conditions) {
            return [];
        }

        $qb->where(implode(
            ' OR ',
            $conditions,
        ));

        return $qb->getQuery()->getResult();
    }

    /**
     * The DQL condition matching a meeting reference in the prompt, with its parameters bound; null when the prompt
     * contains none.
     */
    private function referenceCondition(
        QueryBuilder $qb,
        string $remainder,
    ): ?string {
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
        $meetingRegex = '/(?:(' . implode(
            '|',
            MeetingTypes::getSearchableStrings(),
        ) . ')'
            . ' ([0-9]+))(?:.([0-9]+))?(?:.([0-9]+))?/';
        $meetingInfo = [];
        if (
            1 === preg_match(
                $meetingRegex,
                $remainder,
                $meetingInfo,
                PREG_UNMATCHED_AS_NULL,
            )
        ) {
            /** @psalm-suppress PossiblyNullArgument */
            $meetingType = MeetingTypes::tryFromSearch($meetingInfo[1]);
            $meetingNumber = (int) $meetingInfo[2];

            $where = 'd.meeting_type = :meeting_type AND d.meeting_number = :meeting_number';
            if (null !== $meetingInfo[3]) {
                $where .= ' AND d.point = :point';
                $qb->setParameter(
                    'point',
                    (int) $meetingInfo[3],
                );
            }

            if (null !== $meetingInfo[4]) {
                $where .= ' AND d.number = :number';
                $qb->setParameter(
                    'number',
                    (int) $meetingInfo[4],
                );
            }

            $qb->setParameter(
                'meeting_type',
                $meetingType->value,
            )
                ->setParameter(
                    'meeting_number',
                    $meetingNumber,
                );

            return '(' . $where . ')';
        }

        if (is_numeric($remainder)) {
            $qb->setParameter(
                'meeting_number',
                (int) $remainder,
            );

            return '(d.meeting_number = :meeting_number)';
        }

        return null;
    }
}
