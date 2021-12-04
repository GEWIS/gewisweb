<?php

namespace Activity\Service;

use Activity\Mapper\Proposal;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivityUpdateProposal;
use DateTime;
use Decision\Model\AssociationYear as AssociationYear;
use Decision\Model\Organ;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Laminas\Mvc\I18n\Translator;
use User\Model\User;
use User\Permissions\NotAllowedException;

class ActivityQuery
{
    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var \Decision\Service\Organ
     */
    private $organService;
    /**
     * @var \Activity\Mapper\Activity
     */
    private $activityMapper;
    /**
     * @var Proposal
     */
    private $proposalMapper;
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        \Decision\Service\Organ $organService,
        \Activity\Mapper\Activity $activityMapper,
        Proposal $proposalMapper,
        AclService $aclService
    ) {
        $this->translator = $translator;
        $this->organService = $organService;
        $this->activityMapper = $activityMapper;
        $this->proposalMapper = $proposalMapper;
        $this->aclService = $aclService;
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * A GEWIS association year starts 01-07.
     */
    public const ASSOCIATION_YEAR_START_MONTH = 7;
    public const ASSOCIATION_YEAR_START_DAY = 1;

    /**
     * Get the information of one proposal from the database.
     *
     * @param int $id The proposal id to be searched for
     *
     * @return ActivityUpdateProposal|null or null if the proposal does not exist
     */
    public function getProposal(int $id): ?ActivityUpdateProposal
    {
        return $this->proposalMapper->find($id);
    }

    /**
     * Retrieve all update proposals from the database.
     *
     * @return array a Collection of \Activity\Model\ActivityUpdateProposal
     */
    public function getAllProposals(): array
    {
        return $this->proposalMapper->findAll();
    }

    /**
     * Get an array that states whether a language is available for
     * the provided $activity.
     *
     * @param ActivityModel $activity
     *
     * @return array
     */
    public function getAvailableLanguages($activity)
    {
        return [
            'nl' => !is_null($activity->getName()->getValueNL()),
            'en' => !is_null($activity->getName()->getValueEN()),
        ];
    }

    /**
     * Get the activity with additional details.
     *
     * @param int $id
     *
     * @return ActivityModel|null
     */
    public function getActivityWithDetails(int $id): ?ActivityModel
    {
        if (!$this->aclService->isAllowed('viewDetails', $this->getActivity($id))) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view this activity'));
        }

        return $this->getActivity($id);
    }

    /**
     * Get the information of one activity from the database.
     *
     * @param int $id The activity id to be searched for
     *
     * @return ActivityModel|null Activity or null if the activity does not exist
     */
    public function getActivity(int $id): ?ActivityModel
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        return $this->activityMapper->getActivityById($id);
    }

    /**
     * Returns an array of all activities.
     * NB: This method is currently unused. Should it be removed?
     *
     * @return array Array of activities
     */
    public function findAll()
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        return $this->activityMapper->findAll();
    }

    /**
     * Get all the activities that are yet to be approved.
     *
     * @return array Array of activities
     */
    public function getUnapprovedActivities()
    {
        if (!$this->aclService->isAllowed('viewUnapproved', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view unapproved activities')
            );
        }

        return $this->activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_TO_APPROVE);
    }

    /**
     * Get all activities that are approved by the board.
     *
     * @return array Array of activities
     */
    public function getApprovedActivities()
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view activities'));
        }

        return $this->activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_APPROVED);
    }

    /**
     * Get upcoming activities organized by the given organ.
     *
     * @param Organ $organ
     * @param int $count
     *
     * @return array
     */
    public function getOrganActivities($organ, $count = null)
    {
        return $this->activityMapper->getUpcomingActivities($count, $organ);
    }

    /**
     * Get all activities that are disapproved by the board.
     *
     * @return array Array of activities
     */
    public function getDisapprovedActivities()
    {
        if (!$this->aclService->isAllowed('viewDisapproved', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the disapproved activities')
            );
        }

        return $this->activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_DISAPPROVED);
    }

    /**
     * Get all activities that are approved by the board and which occur in the future.
     *
     * @param string $category Type of activities requested
     *
     * @return array Array of activities
     */
    public function getUpcomingActivities($category = null)
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view upcoming the activities')
            );
        }

        if ('my' === $category) {
            if (!$this->aclService->isAllowed('view', 'myActivities')) {
                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to view upcoming activities coupled to a member account')
                );
            }
            $user = $this->aclService->getIdentityOrThrowException();

            return $this->activityMapper->getUpcomingActivitiesForMember($user);
        }

        return $this->activityMapper->getUpcomingActivities(null, null, $category);
    }

    /**
     * Gets the upcoming activities created by this user or its organs.
     * Or, when the user is an admin, retrieve all upcoming activities.
     *
     * @param User $user
     *
     * @return array
     */
    public function getUpcomingCreatedActivities($user)
    {
        if ($this->aclService->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $this->activityMapper->getAllUpcomingActivities();
        }
        $organs = $this->organService->getEditableOrgans();

        return $this->activityMapper->getAllUpcomingActivities($organs, $user->getLidnr());
    }

    /**
     * Gets a paginator for the old activities created by this user or its organs.
     * Or, when the user is an admin, retrieve all old activities.
     *
     * @param User $user
     *
     * @return DoctrinePaginator
     */
    public function getOldCreatedActivitiesPaginator($user)
    {
        if ($this->aclService->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $this->activityMapper->getOldActivityPaginatorAdapterByOrganizer();
        }
        $organs = $this->organService->getEditableOrgans();

        return $this->activityMapper->getOldActivityPaginatorAdapterByOrganizer($organs, $user->getLidnr());
    }

    /**
     * Get all the years activities have taken place in the past.
     *
     * @return array
     */
    public function getActivityArchiveYears()
    {
        $oldest = $this->activityMapper->getOldestActivity();

        if (null === $oldest) {
            return [];
        }

        $startYear = AssociationYear::fromDate($oldest->getBeginTime())->getYear();
        $endYear = AssociationYear::fromDate(new DateTime())->getYear();

        // We make the reasonable assumption that there is at least one activity
        return range($startYear, $endYear);
    }

    /**
     * Get all the activities that have finished in a year (and thus are archived)
     *
     * @param int $year First part of study year
     *
     * @return array
     */
    public function getFinishedActivitiesByYear(int $year)
    {
        if (!$this->aclService->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        $associationYear = AssociationYear::fromYear($year);

        $endDate = $associationYear->getEndDate() < new DateTime() ? $associationYear->getEndDate() : new DateTime();

        return $this->activityMapper->getArchivedActivitiesInRange($associationYear->getStartDate(), $endDate);
    }
}
