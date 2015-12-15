<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;
use Activity\Model\Activity as ActivityModel;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Activity\Form\Activity as ActivityForm;

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
     * Return the form for this activity
     *
     * @return ActivityForm
     */
    public function getForm()
    {
        $this->permissionCreateActivityOrException();
        return new ActivityForm();
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
        $this->permissionViewActivityOrException();

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
        $this->permissionViewActivityOrException();

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
        $this->permissionManageActivityOrException();

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
        $this->permissionViewActivityOrException();

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
        $this->permissionManageActivityOrException();

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
        $this->permissionCreateActivityOrException();

        assert($dutch || $english, "Activities should have either be in dutch or english");

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        // Find the creator
        $user = $em->merge(
            $this->getServiceManager()->get('user_role')
        );

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

        if (isset($params['fields'])) {
            foreach ($params['fields'] as $fieldparams){

                $field = new ActivityField();
                $field->create($fieldparams, $this, $em);
                $em->persist($field);
            }
            $em->flush();
        }

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
        $this->permissionManageActivityOrException();

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
        $this->permissionManageActivityOrException();

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
        $this->permissionManageActivityOrException();

        $activity->setStatus(ActivityModel::STATUS_DISAPPROVED);
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->persist($activity);
        $em->flush();
    }

    /*
     * Some general ACL functions
     */

    /**
     * Checks if the user is allowed to view the activity, and if not, throws an used permission exception
     */
    public function permissionViewActivityOrException()
    {
        if (!$this->isAllowed('view', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the activities')
            );
        }
    }

    /**
     * Can the user create the activity?
     */
    public function permissionCreateActivityOrException()
    {
        if (!$this->isAllowed('create', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity')
            );
        }
    }

    /**
     * Can the user manage an activity
     */
    public function permissionManageActivityOrException()
    {
        if (!$this->isAllowed('manage', 'activity')){
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to manage an activity')
            );
        }
    }
}
