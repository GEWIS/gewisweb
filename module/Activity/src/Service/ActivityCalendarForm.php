<?php

declare(strict_types=1);

namespace Activity\Service;

use Activity\Mapper\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper;
use Activity\Mapper\ActivityOptionProposal as ActivityOptionProposalMapper;
use Activity\Mapper\MaxActivities as MaxActivitiesMapper;
use Activity\Model\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodModel;
use DateTime;
use Decision\Model\Organ as OrganModel;
use Decision\Service\Organ as OrganService;
use Exception;

use function count;

class ActivityCalendarForm
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly OrganService $organService,
        private readonly ActivityOptionCreationPeriodMapper $periodMapper,
        private readonly MaxActivitiesMapper $maxActivitiesMapper,
        private readonly ActivityOptionProposalMapper $optionProposalMapper,
    ) {
    }

    /**
     * Returns whether a user may create an option with given start time.
     *
     * @throws Exception
     */
    public function canCreateOptionInPeriod(
        int $period,
        DateTime $beginTime,
        DateTime $endTime,
    ): bool {
        if ($this->aclService->isAllowed('create_always', 'activity_calendar_proposal')) {
            return true;
        }

        // If not allowed to `create_always`, -1 certainly cannot be accepted.
        if (-1 === $period) {
            return false;
        }

        /** @var ActivityOptionCreationPeriodModel|null $period */
        $period = $this->periodMapper->find($period);

        if (null === $period) {
            return false;
        }

        return $period->getBeginOptionTime() <= $beginTime
            && $period->getEndOptionTime() > $beginTime
            && $period->getBeginOptionTime() < $endTime
            && $period->getEndOptionTime() >= $endTime;
    }

    /**
     * Get the current ActivityOptionCreationPeriod.
     *
     * @return ActivityOptionCreationPeriodModel[]
     */
    public function getCurrentPeriods(): array
    {
        return $this->periodMapper->getCurrentActivityOptionCreationPeriods();
    }

    /**
     * Retrieves all organs which the current user is allowed to edit and for which the organ can create proposals.
     *
     * @return OrganModel[]
     *
     * @throws Exception
     */
    public function getEditableOrgans(): array
    {
        $allOrgans = $this->organService->getEditableOrgans();
        $organs = [];
        foreach ($allOrgans as $organ) {
            $organId = $organ->getId();

            if (!$this->canOrganCreateProposal($organId)) {
                continue;
            }

            $organs[] = $organ;
        }

        return $organs;
    }

    /**
     * Returns whether an organ may create a new activity proposal.
     *
     * @throws Exception
     */
    public function canOrganCreateProposal(int $organId): bool
    {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_proposal')) {
            return false;
        }

        if ($this->aclService->isAllowed('create_always', 'activity_calendar_proposal')) {
            return true;
        }

        $periods = $this->getCurrentPeriods();
        if (empty($periods)) {
            return false;
        }

        $totalMaxActivities = 0;
        $totalCount = 0;
        foreach ($periods as $period) {
            $totalMaxActivities += $this->getMaxActivities($organId, $period->getId());
            $totalCount += $this->getCurrentProposalCount($period, $organId);
        }

        return $totalCount < $totalMaxActivities;
    }

    /**
     * Get the current proposal count of an organ for the given period.
     */
    protected function getCurrentProposalCount(
        ActivityOptionCreationPeriodModel $period,
        int $organId,
    ): int {
        $mapper = $this->optionProposalMapper;
        $begin = $period->getBeginPlanningTime();
        $end = $period->getEndPlanningTime();

        return count($mapper->getNonClosedProposalsWithinPeriodAndOrgan($begin, $end, $organId));
    }

    /**
     * Get the max number of activity options an organ can create.
     *
     * @throws Exception
     */
    protected function getMaxActivities(
        int $organId,
        int $periodId,
    ): int {
        $mapper = $this->maxActivitiesMapper;
        $maxActivities = $mapper->getMaxActivityOptionsByOrganPeriod($organId, $periodId);

        $max = 0;
        if (null !== $maxActivities) {
            $max = $maxActivities->getValue();
        }

        return $max;
    }

    public static function toDateTime(
        string $value,
        string $format = 'Y-m-d',
    ): DateTime {
        return DateTime::createFromFormat($format, $value);
    }
}
