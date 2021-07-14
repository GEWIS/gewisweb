<?php

namespace Activity\Service;

use Activity\Mapper\Proposal;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivityUpdateProposal;
use Application\Service\AbstractAclService;
use DateTime;
use Decision\Model\AssociationYear as AssociationYear;
use Decision\Model\Organ;
use Doctrine\Common\Collections\Collection;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use User\Model\User;
use User\Permissions\NotAllowedException;

class ActivityQuery extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;
    /**
     * @var Acl
     */
    private $acl;
    /**
     * @var \User\Service\User
     */
    private $userService;
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

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        \User\Service\User $userService,
        \Decision\Service\Organ $organService,
        \Activity\Mapper\Activity $activityMapper,
        Proposal $proposalMapper
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->userService = $userService;
        $this->organService = $organService;
        $this->activityMapper = $activityMapper;
        $this->proposalMapper = $proposalMapper;
    }

    public function getRole()
    {
        return $this->userRole;
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
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Get the information of one proposal from the database.
     *
     * @param int $id The proposal id to be searched for
     *
     * @return ActivityUpdateProposal or null if the proposal does not exist
     */
    public function getProposal($id)
    {
        $proposalMapper = $this->proposalMapper;

        return $proposalMapper->getProposalById($id);
    }

    /**
     * Retrieve all update proposals from the database.
     *
     * @return Collection a Collection of \Activity\Model\ActivityUpdateProposal
     */
    public function getAllProposals()
    {
        return $this->proposalMapper->getAllProposals();
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
        return ['nl' => !is_null($activity->getName()->getValueNL()),
            'en' => !is_null($activity->getName()->getValueEN()),];
    }

    /**
     * Get the activity with additional details.
     *
     * @param int $id
     *
     * @return ActivityModel
     */
    public function getActivityWithDetails($id)
    {
        if (!$this->isAllowed('viewDetails', $this->getActivity($id))) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view this activity'));
        }

        return $this->getActivity($id);
    }

    /**
     * Get the information of one activity from the database.
     *
     * @param int $id The activity id to be searched for
     *
     * @return ActivityModel Activity or null if the activity does not exist
     */
    public function getActivity($id)
    {
        if (!$this->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        $activityMapper = $this->activityMapper;

        return $activityMapper->getActivityById($id);
    }

    /**
     * Returns an array of all activities.
     * NB: This method is currently unused. Should it be removed?
     *
     * @return Collection Array of activities
     */
    public function getAllActivities()
    {
        if (!$this->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        $activityMapper = $this->activityMapper;

        return $activityMapper->getAllActivities();
    }

    /**
     * Get all the activities that are yet to be approved.
     *
     * @return Collection Array of activities
     */
    public function getUnapprovedActivities()
    {
        if (!$this->isAllowed('viewUnapproved', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view unapproved activities')
            );
        }

        $activityMapper = $this->activityMapper;

        return $activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_TO_APPROVE);
    }

    /**
     * Get all activities that are approved by the board.
     *
     * @return Collection Array of activities
     */
    public function getApprovedActivities()
    {
        if (!$this->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view activities'));
        }

        $activityMapper = $this->activityMapper;

        return $activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_APPROVED);
    }

    /**
     * Get upcoming activities organized by the given organ.
     *
     * @param Organ $organ
     * @param int $count
     *
     * @return Collection
     */
    public function getOrganActivities($organ, $count = null)
    {
        return $this->getActivityMapper()->getUpcomingActivities($count, $organ);
    }

    /**
     * Get the activity mapper.
     *
     * @return \Activity\Mapper\Activity
     */
    public function getActivityMapper()
    {
        return $this->activityMapper;
    }

    /**
     * Get all activities that are disapproved by the board.
     *
     * @return Collection Array of activities
     */
    public function getDisapprovedActivities()
    {
        if (!$this->isAllowed('viewDisapproved', 'activity')) {
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
     * @return Collection Array of activities
     */
    public function getUpcomingActivities($category = null)
    {
        if (!$this->isAllowed('view', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view upcoming the activities')
            );
        }

        if ('my' === $category) {
            if (!$this->isAllowed('view', 'myActivities')) {
                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to view upcoming activities coupled to a member account')
                );
            }
            $user = $this->userService->getIdentity();

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
     * @return Collection
     */
    public function getUpcomingCreatedActivities($user)
    {
        $activityMapper = $this->getActivityMapper();
        if ($this->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $activityMapper->getAllUpcomingActivities();
        }
        $organs = $this->organService->getEditableOrgans();

        return $activityMapper->getAllUpcomingActivities($organs, $user->getLidnr());
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
        $activityMapper = $this->getActivityMapper();
        if ($this->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $activityMapper->getOldActivityPaginatorAdapterByOrganizer();
        }
        $organs = $this->organService->getEditableOrgans();

        return $activityMapper->getOldActivityPaginatorAdapterByOrganizer($organs, $user->getLidnr());
    }

    /**
     * Get all the years activities have taken place in the past.
     *
     * @return array
     */
    public function getActivityArchiveYears()
    {
        $oldest = $this->getActivityMapper()->getOldestActivity();
        if (is_null($oldest) || is_null($oldest->getBeginTime())) {
            return [null];
        }

        $startYear = AssociationYear::fromDate($oldest->getBeginTime())->getYear();
        $endYear = AssociationYear::fromDate(new DateTime())->getYear();

        // We make the reasonable assumption that there is at least one activity
        return range($startYear, $endYear);
    }

    /**
     * Get all the activities that have finished in a year (and thus are archived.
     *
     * @param int $year First part of study year
     *
     * @return array
     */
    public function getFinishedActivitiesByYear($year)
    {
        if (!$this->isAllowed('view', 'activity')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        $associationYear = AssociationYear::fromYear($year);

        $endDate = $associationYear->getEndDate() < new DateTime() ? $associationYear->getEndDate() : new DateTime();

        return $this->getActivityMapper()->getArchivedActivitiesInRange($associationYear->getStartDate(), $endDate);
    }

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activity';
    }
}
