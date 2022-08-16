<?php

namespace Activity\Service;

use Activity\Mapper\{
    ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper,
    ActivityOptionProposal as ActivityOptionProposalMapper,
    MaxActivities as MaxActivitiesMapper,
};
use Activity\Model\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodModel;
use DateTime;
use Decision\Service\Organ as OrganService;
use Exception;

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
     * @param DateTime $beginTime
     *
     * @return bool
     *
     * @throws Exception
     */
    public function canCreateOption(DateTime $beginTime): bool
    {
        if ($this->aclService->isAllowed('create_always', 'activity_calendar_proposal')) {
            return true;
        }

        $period = $this->getCurrentPeriod();

        if (null === $period) {
            return false;
        }

        $begin = $period->getBeginOptionTime();
        $end = $period->getEndOptionTime();

        if ($begin > $beginTime || $beginTime > $end) {
            return false;
        }

        return true;
    }

    /**
     * Get the current ActivityOptionCreationPeriod.
     *
     * @return ActivityOptionCreationPeriodModel|null
     *
     * @throws Exception
     */
    public function getCurrentPeriod(): ?ActivityOptionCreationPeriodModel
    {
        $mapper = $this->periodMapper;

        return $mapper->getCurrentActivityOptionCreationPeriod();
    }

    /**
     * Retrieves all organs which the current user is allowed to edit and for which the organs can still create proposals.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getEditableOrgans(): array
    {
        $allOrgans = $this->organService->getEditableOrgans();
        $organs = [];
        foreach ($allOrgans as $organ) {
            $organId = $organ->getId();
            if ($this->canOrganCreateProposal($organId)) {
                $organs[] = $organ;
            }
        }

        return $organs;
    }

    /**
     * Returns whether an organ may create a new activity proposal.
     *
     * @param int $organId
     *
     * @return bool
     *
     * @throws Exception
     */
    public function canOrganCreateProposal(int $organId): bool
    {
        if ($this->aclService->isAllowed('create_always', 'activity_calendar_proposal')) {
            return true;
        }

        $period = $this->getCurrentPeriod();

        if (
            null === $period
            || !$this->aclService->isAllowed('create', 'activity_calendar_proposal')
            || null == $organId
        ) {
            return false;
        }

        $max = $this->getMaxActivities($organId, $period->getId());
        $count = $this->getCurrentProposalCount($period, $organId);

        return $count < $max;
    }

    /**
     * Get the current proposal count of an organ for the given period.
     *
     * @param ActivityOptionCreationPeriodModel $period
     * @param int $organId
     *
     * @return int
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
     * @param int $organId
     * @param int $periodId
     *
     * @return int
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

    /**
     * @param string $value
     * @param string $format
     *
     * @return DateTime
     */
    public static function toDateTime(
        string $value,
        string $format = 'Y-m-d',
    ): DateTime {
        return DateTime::createFromFormat($format, $value);
    }
}
