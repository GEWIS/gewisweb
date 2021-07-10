<?php

namespace Activity\Service;

use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivityUpdateProposal;
use Application\Service\AbstractAclService;
use DateTime;
use Decision\Model\AssociationYear as AssociationYear;
use Decision\Model\Organ;
use User\Permissions\NotAllowedException;
use User\Service\User;
use Zend\Mvc\I18n\Translator;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class ActivityQuery extends AbstractAclService implements ServiceManagerAwareInterface
{

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

    /**
     * Set the service manager.
     *
     * @param ServiceManager $sm
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    public function getRole()
    {
        return $this->sm->get('user_role');
    }
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
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
     * A GEWIS association year starts 01-07
     */
    const ASSOCIATION_YEAR_START_MONTH = 7;
    const ASSOCIATION_YEAR_START_DAY = 1;

    /**
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->sm->get('activity_acl');
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
        $proposalMapper = $this->sm->get('activity_mapper_proposal');

        return $proposalMapper->getProposalById($id);
    }

    /**
     * Retrieve all update proposals from the database.
     *
     * @return a Collection of \Activity\Model\ActivityUpdateProposal
     */
    public function getAllProposals()
    {
        return $this->sm->get('activity_mapper_proposal')->getAllProposals();
    }

    /**
     * Get an array that states whether a language is available for
     * the provided $activity
     *
     * @param ActivityModel $activity
     * @return array
     */
    public function getAvailableLanguages($activity)
    {
        return ['nl' => !is_null($activity->getName()->getValueNL()),
            'en' => !is_null($activity->getName()->getValueEN())];
    }

    /**
     * Get the activity with additional details
     *
     * @param $id
     * @return ActivityModel
     */
    public function getActivityWithDetails($id)
    {
        if (!$this->isAllowed('viewDetails', $this->getActivity($id))) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view this activity')
            );
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
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the activities')
            );
        }

        $activityMapper = $this->sm->get('activity_mapper_activity');

        return $activityMapper->getActivityById($id);
    }

    /**
     * Returns an array of all activities.
     * NB: This method is currently unused. Should it be removed?
     * @return array Array of activities
     */
    public function getAllActivities()
    {
        if (!$this->isAllowed('view', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the activities')
            );
        }

        $activityMapper = $this->sm->get('activity_mapper_activity');

        return $activityMapper->getAllActivities();
    }

    /**
     * Get all the activities that are yet to be approved
     *
     * @return array Array of activities
     */
    public function getUnapprovedActivities()
    {
        if (!$this->isAllowed('viewUnapproved', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view unapproved activities')
            );
        }

        $activityMapper = $this->sm->get('activity_mapper_activity');
        return $activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_TO_APPROVE);
    }

    /**
     * Get all activities that are approved by the board
     *
     * @return array Array of activities
     */
    public function getApprovedActivities()
    {
        if (!$this->isAllowed('view', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view activities')
            );
        }

        $activityMapper = $this->sm->get('activity_mapper_activity');
        return $activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_APPROVED);
    }

    /**
     * Get upcoming activities organized by the given organ.
     *
     * @param Organ $organ
     * @param integer $count
     *
     * @return array
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
        return $this->sm->get('activity_mapper_activity');
    }

    /**
     * Get all activities that are disapproved by the board
     *
     * @return array Array of activities
     */
    public function getDisapprovedActivities()
    {
        if (!$this->isAllowed('viewDisapproved', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the disapproved activities')
            );
        }

        $activityMapper = $this->sm->get('activity_mapper_activity');
        return $activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_DISAPPROVED);
    }

    /**
     * Get all activities that are approved by the board and which occur in the future
     *
     * @param String $category Type of activities requested
     *
     * @return array Array of activities
     */
    public function getUpcomingActivities($category = null)
    {
        if (!$this->isAllowed('view', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view upcoming the activities')
            );
        }

        $activityMapper = $this->sm->get('activity_mapper_activity');
        if ($category === 'my') {
            if (!$this->isAllowed('view', 'myActivities')) {

                throw new NotAllowedException(
                    $this->translator->translate('You are not allowed to view upcoming activities coupled to a member account')
                );
            }
            $user = $this->getUserService()->getIdentity();
            return $activityMapper->getUpcomingActivitiesForMember($user);
        }
        return $activityMapper->getUpcomingActivities(null, null, $category);
    }

    /**
     * Gets the user service.
     *
     * @return User
     */
    public function getUserService()
    {
        return $this->sm->get('user_service_user');
    }

    /**
     * Gets the upcoming activities created by this user or its organs.
     * Or, when the user is an admin, retrieve all upcoming activities
     *
     * @param /User/Model/User $user
     * @return array
     */
    public function getUpcomingCreatedActivities($user)
    {
        $activityMapper = $this->getActivityMapper();
        if ($this->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $activityMapper->getAllUpcomingActivities();
        }
        $organs = $this->sm->get('decision_service_organ')->getEditableOrgans();
        return $activityMapper->getAllUpcomingActivities($organs, $user->getLidnr());
    }

    /**
     * Gets a paginator for the old activities created by this user or its organs.
     * Or, when the user is an admin, retrieve all old activities
     *
     * @param \User\Model\User $user
     * @return array
     */
    public function getOldCreatedActivitiesPaginator($user)
    {
        $activityMapper = $this->getActivityMapper();
        if ($this->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $activityMapper->getOldActivityPaginatorAdapterByOrganizer();
        }
        $organs = $this->sm->get('decision_service_organ')->getEditableOrgans();
        return $activityMapper->getOldActivityPaginatorAdapterByOrganizer($organs, $user->getLidnr());
    }

    /**
     * Get all the years activities have taken place in the past
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
     * Get all the activities that have finished in a year (and thus are archived
     *
     * @param integer $year First part of study year
     * @return array
     */
    public function getFinishedActivitiesByYear($year)
    {
        if (!$this->isAllowed('view', 'activity')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the activities')
            );
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
