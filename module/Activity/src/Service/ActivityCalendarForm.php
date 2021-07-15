<?php

namespace Activity\Service;

use Activity\Mapper\ActivityOptionProposal;
use Activity\Mapper\MaxActivities;
use Activity\Model\ActivityOptionCreationPeriod;
use DateTime;
use Decision\Service\Organ;
use Exception;

class ActivityCalendarForm
{
    private AclService $aclService;
    private Organ $organService;
    private \Activity\Mapper\ActivityOptionCreationPeriod $periodMapper;
    private MaxActivities $maxActivitiesMapper;
    private ActivityOptionProposal $optionProposalMapper;

    public function __construct(
        AclService $aclService,
        Organ $organService,
        \Activity\Mapper\ActivityOptionCreationPeriod $periodMapper,
        MaxActivities $maxActivitiesMapper,
        ActivityOptionProposal $optionProposalMapper
    ) {

        $this->aclService = $aclService;
        $this->organService = $organService;
        $this->periodMapper = $periodMapper;
        $this->maxActivitiesMapper = $maxActivitiesMapper;
        $this->optionProposalMapper = $optionProposalMapper;
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
    public function canCreateOption($beginTime)
    {
        if ($this->aclService->isAllowed('create_always', 'activity_calendar_proposal')) {
            return true;
        }

        $period = $this->getCurrentPeriod();
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
     * @return ActivityOptionCreationPeriod
     *
     * @throws Exception
     */
    public function getCurrentPeriod()
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
    public function getEditableOrgans()
    {
        $allOrgans = $this->organService->getEditableOrgans();
        $organs = [];
        foreach ($allOrgans as $organ) {
            $organId = $organ->getId();
            if ($this->canOrganCreateProposal($organId)) {
                array_push($organs, $organ);
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
    public function canOrganCreateProposal($organId)
    {
        if ($this->aclService->isAllowed('create_always', 'activity_calendar_proposal')) {
            return true;
        }

        $period = $this->getCurrentPeriod();

        if (
            null == $period
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
     * @param ActivityOptionCreationPeriod $period
     * @param int $organId
     *
     * @return int
     */
    protected function getCurrentProposalCount($period, $organId)
    {
        $mapper = $this->optionProposalMapper;
        $begin = $period->getBeginPlanningTime();
        $end = $period->getEndPlanningTime();
        $options = $mapper->getNonClosedProposalsWithinPeriodAndOrgan($begin, $end, $organId);

        return count($options);
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
    protected function getMaxActivities($organId, $periodId)
    {
        $mapper = $this->maxActivitiesMapper;
        $maxActivities = $mapper->getMaxActivityOptionsByOrganPeriod($organId, $periodId);
        // TODO: The initial value of $max below represents a default value for when no appropriate MaxActivities instance exists.
        $max = 2;
        if ($maxActivities) {
            $max = $maxActivities->getValue();
        }

        return $max;
    }

    public static function toDateTime($value, $format = 'd/m/Y')
    {
        return DateTime::createFromFormat($format, $value);
    }
}
