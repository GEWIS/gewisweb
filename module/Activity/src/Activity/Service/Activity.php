<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;

class Activity extends AbstractAclService implements \Zend\ServiceManager\ServiceManagerAwareInterface
{
    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        //todo, this;
        return $this->getServiceManager()->get('education_acl');
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
    public function getActivity($id) {
        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getActivityById($id);
        return $activity;
    }

    /**
     * Returns an array of all activities
     * @return array Array of activities
     */
    public function getAllActivities(){
        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getAllActivities();
        return $activity;
    }

}
