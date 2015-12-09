<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;
use Activity\Model\Activity as ActivityModel;
use User\Permissions\NotAllowedException;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class Activity extends AbstractAclService implements ServiceManagerAwareInterface
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
     * Get the information of one activity from the database.
     *
     * @param int $id The activity id to be searched for
     *
     * @return \Activity\Model\Activity Activity or null if the activity does not exist
     */
    public function getActivity($id)
    {
        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getActivityById($id);

        return $activity;
    }

    /**
     * Returns an array of all activities.
     *
     * @return array Array of activities
     */
    public function getAllActivities()
    {
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
        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getUnapprovedActivities();

        return $activity;
    }

    /**
     * Get all activities that are approved by the board
     *
     * @return array Array of activities
     */
    public function getApprovedActivities()
    {
        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getApprovedActivities();

        return $activity;
    }


    /**
     * Get all activities that are disapproved by the board
     *
     * @return array Array of activities
     */
    public function getDisapprovedActivities()
    {
        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getDisapprovedActivities();

        return $activity;
    }

    /**
     * Create an activity from parameters.
     *
     * @param array $params Parameters describing activity
     *
     * @return ActivityModel Activity that was created.
     */
    public function createActivity(array $params, $dutch, $english)
    {
        assert($dutch || $english, "Activities should have either be in dutch or english");

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        // Find the creator
        $user = $this->getServiceManager()->get('user_role');
        if ($user === 'guest') {
            throw new NotAllowedException('Guests can not create activities');
        }

        $user = $em->merge($user);
        $activity = new ActivityModel();
        $activity->setBeginTime(new \DateTime($params['beginTime']));
        $activity->setEndTime(new \DateTime($params['endTime']));

        if ($dutch ) {
            $activity->setName($params['name']);
            $activity->setLocation($params['location']);
            if (!$params['costs_unknown']) {
                $activity->setCostsEn($params['costs']);
            }
            $activity->setDescription($params['description']);
        } else if ($english) {
            $activity->setNameEn($params['name_en']);
            $activity->setLocationEn($params['location_en']);
            if (!$params['costs_unknown']) {
                $activity->setCostsEn($params['costs_en']);
            }
            $activity->setDescriptionEn($params['description_en']);
        }


        $activity->setCanSignUp($params['canSignUp']);

        // Not user provided input
        $activity->setCreator($user);
        $activity->setStatus(ActivityModel::STATUS_TO_APPROVE);
        $activity->setOnlyGEWIS(true); // Not yet implemented

        $em->persist($activity);
        $em->flush();

        return $activity;
    }


    /**
     * Approve of an activity
     *
     * @param ActivityModel $activity
     */
    public function approve(ActivityModel $activity)
    {
        $activity->setStatus(ActivityModel::STATUS_APPROVED);
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->persist($activity);
        $em->flush();
    }

    /**
     * Reset the approval status of an activity
     *
     * @param ActivityModel $activity
     */
    public function reset(ActivityModel $activity)
    {
        $activity->setStatus(ActivityModel::STATUS_TO_APPROVE);
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->persist($activity);
        $em->flush();
    }

    /**
     * Disapprove of an activity
     *
     * @param ActivityModel $activity
     */
    public function disapprove(ActivityModel $activity)
    {
        $activity->setStatus(ActivityModel::STATUS_DISAPPROVED);
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->persist($activity);
        $em->flush();
    }
}
