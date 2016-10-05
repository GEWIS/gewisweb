<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;
use Activity\Model\Activity as ActivityModel;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class ActivityQuery extends AbstractAclService implements ServiceManagerAwareInterface
{
    /**
     * A GEWIS association year starts 01-07
     */
    const ASSOCIATION_YEAR_START_MONTH = 7;
    const ASSOCIATION_YEAR_START_DAY = 1;
    
    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('activity_acl');
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


    /**
     * Get the information of one activity from the database.
     *
     * @param int $id The activity id to be searched for
     *
     * @return \Activity\Model\Activity Activity or null if the activity does not exist
     */
    public function getActivity($id)
    {
        if (!$this->isAllowed('view', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the activities')
            );
        }

        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getActivityById($id);

        return $activity;
    }

    /**
     * Get the information of one proposal from the database.
     *
     * @param int $id The proposal id to be searched for
     *
     * @return \Activity\Model\ActivityUpdateProposal or null if the proposal does not exist
     */
    public function getProposal($id)
    {
        $proposalMapper = $this->getServiceManager()->get('activity_mapper_proposal');
        $proposal = $proposalMapper->getProposalById($id);

        return $proposal;
    }

    /**
     * Retrieve all update proposals from the database.
     *
     * @return a Collection of \Activity\Model\ActivityUpdateProposal
     */
    public function getAllProposals()
    {
        return $this->getServiceManager()->get('activity_mapper_proposal')->getAllProposals();
    }

    /**
     * Get an array that states whether a language is available for
     * the provided $activity
     *
     * @param ActivityModel $activity
     * @return string
     */
    public function getAvailableLanguages($activity)
    {
        return ['nl' => !is_null($activity->getName()),
                'en' => !is_null($activity->getNameEn())];
    }

    /**
     * Get the activity with additional details
     *
     * @param $id
     * @return ActivityModel
     */
    public function getActivityWithDetails($id)
    {
        if (!($this->isAllowed('viewDetails', 'activity') ||
                $this->isAllowed('viewDetails', $this->getActivity($id)))) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the activities')
            );
        }
        return $this->getActivity($id);
    }

    /**
     * Returns an array of all activities.
     * NB: This method is currently unused. Should it be removed?
     * @return array Array of activities
     */
    public function getAllActivities()
    {
        if (!$this->isAllowed('view', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the activities')
            );
        }

        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getAllActivities();

        return $activity;
    }

    /**
     * Get all the activities that are yet to be approved
     *
     * @return array Array of activities
     */
    public function getUnapprovedActivities()
    {
        if (!$this->isAllowed('viewUnapproved', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view unapproved activities')
            );
        }

        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
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
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view activities')
            );
        }

        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        return $activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_APPROVED);
    }

    /**
     * Get upcoming activities organized by the given organ.
     *
     * @param \Organ\Model\Organ $organ
     * @param integer $count
     *
     * @return array
     */
    public function getOrganActivities($organ, $count = null)
    {
        return $this->getActivityMapper()->getUpcomingActivities($count, $organ);
    }

    /**
     * Get all activities that are disapproved by the board
     *
     * @return array Array of activities
     */
    public function getDisapprovedActivities()
    {
        if (!$this->isAllowed('viewDisapproved', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the disapproved activities')
            );
        }

        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        return $activityMapper->getAllUpcomingActivities(null, null, ActivityModel::STATUS_DISAPPROVED);
    }

    /**
     * Get all activities that are approved by the board and which occur in the future
     *
     * @return array Array of activities
     */
    public function getUpcomingActivities($category = null)
    {
        if (!$this->isAllowed('view', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view upcoming the activities')
            );
        }

        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getUpcomingActivities(null, null, $category);

        return $activity;
    }

    /**
     * Gets the upcoming activities created by this user or its organs.
     * Or, when the user is an admin, retrieve all upcoming activities
     *
     * @param type $user
     * @return type
     */
    public function getUpcomingCreatedActivities($user)
    {
        $activityMapper = $this->getActivityMapper();
        if ($this->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $activityMapper->getAllUpcomingActivities();
        }
        $organs = $this->getServiceManager()->get('decision_service_organ')->getEditableOrgans();
        return $activityMapper->getAllUpcomingActivities($organs, $user->getLidnr());
    }

    /**
     * Gets a paginator for the old activities created by this user or its organs.
     * Or, when the user is an admin, retrieve all old activities
     *
     * @param type $user
     * @return type
     */
    public function getOldCreatedActivitiesPaginator($user)
    {
        $activityMapper = $this->getActivityMapper();
        if ($this->isAllowed('viewDetails', 'activity')) {
            //Only admins are allowed to unconditionally view activity details
            return $activityMapper->getOldActivityPaginatorAdapterByOrganizer();
        }
        $organs = $this->getServiceManager()->get('decision_service_organ')->getEditableOrgans();
        return $activityMapper->getOldActivityPaginatorAdapterByOrganizer($organs, $user->getLidnr());
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
     * Get all the years activities took place
     *
     * @return array
     */
    public function getActivityYears()
    {
        $oldest = $this->getActivityMapper()->getOldestActivity();
        $newest = $this->getActivityMapper()->getNewestActivity();
        if (is_null($oldest) || is_null($newest) || is_null($oldest->getBeginTime()) || is_null($newest->getBeginTime())) {
            return [null];
        }

        $startYear = $this->getAssociationYear($oldest->getBeginTime());
        $endYear = $this->getAssociationYear($newest->getBeginTime());

        // We make the reasonable assumption that at least one photo is taken every year
        return range($startYear, $endYear);
    }

    /**
     * Returns the association year to which a certain date belongs
     * In this context an association year is defined as the year which contains
     * the first day of the association year.
     *
     * Example: A value of 2010 would represent the association year 2010/2011
     *
     * @param \DateTime $date
     *
     * @return int representing an association year.
     */
    protected function getAssociationYear(\DateTime $date)
    {
        if ($date->format('n') < self::ASSOCIATION_YEAR_START_MONTH) {
            return $date->format('Y') - 1;
        } else {
            return $date->format('Y');
        }
    }
}
