<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;
use Activity\Model\Activity as ActivityModel;

class Activity extends AbstractAclService implements \Zend\ServiceManager\ServiceManagerAwareInterface
{
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
     * Get the information of one activity from the database
     * @param $id The activity id to be searched for
     *
     * @return Activity\Model\Activity Activity or null if the activity does not exist
     */
    public function getActivity($id)
    {
        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getActivityById($id);
        return $activity;
    }

    /**
     * Returns an array of all activities
     * @return array Array of activities
     */
    public function getAllActivities()
    {
        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getAllActivities();
        return $activity;
    }
	
	/**
     * Returns an array of all organs the user may add events for
     * @return array Array of activities
     */
	public function getOrgans(){
		$sm = $this->getServiceManager();
		$user = $sm->get("user_role");
		if($user instanceOf \User\Model\User){
			return $user->getOrganRoleNames();
		}else{
			return array();
		}
	}

    /**
     * Create an activity from parameters
     *
     * @param array $params Parameters describing activity
     * @return ActivityModel Activity that was created.
     */
    public function createActivity(array $params)
    {
        $activity = new ActivityModel();
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $activity->create($params);
        $em->persist($activity);
        $em->flush();
        return $activity;
    }

}
