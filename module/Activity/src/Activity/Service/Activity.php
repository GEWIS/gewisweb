<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivityField as ActivityFieldModel;
use Activity\Model\ActivityOption as ActivityOptionModel;
use Decision\Model\Organ;
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
        if (!$this->isAllowed('create', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity')
            );
        }

        return $this->getServiceManager()->get('activity_form_activity');
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
     * Returns an array of all activities.
     *
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
                $translator->translate('You are not allowed to view the activities')
            );
        }

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
        if (!$this->isAllowed('view', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view approved the activities')
            );
        }

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
        if (!$this->isAllowed('viewDisapproved', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the disapproved activities')
            );
        }

        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getDisapprovedActivities();

        return $activity;
    }

    /**
     * Get all activities that are approved by the board and which occur in the future
     *
     * @return array Array of activities
     */
    public function getUpcomingActivities()
    {
        if (!$this->isAllowed('view', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view upcoming the activities')
            );
        }

        $activityMapper = $this->getServiceManager()->get('activity_mapper_activity');
        $activity = $activityMapper->getUpcomingActivities();

        return $activity;
    }

    /**
     * Create an activity from parameters.
     * @pre $params is valid data of Activity\Form\Activity
     *
     * @param array $params Parameters describing activity
     *
     * @return ActivityModel Activity that was created.
     */
    public function createActivity(array $params, $dutch, $english)
    {
        assert($dutch || $english, "Activities should have either be in dutch or english");

        if (!$this->isAllowed('create', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity')
            );
        }

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        // Find the creator
        /** @var \User\Model\User $user */
        $user = $em->merge(
            $this->getServiceManager()->get('user_role')
        );



        // Find the organ the activity belongs to, and see if the user has permission to create an activity
        // for this organ
        $organId = intval($params['organ']);

        $organ = null;

        // If the organ is 0 then the activity does not belong to an organ
        if ($organId !== 0) {
            /** @var \Decision\Service\Member $memberService */
            $memberService = $this->getServiceManager()->get('decision_service_member');
            $member = $memberService->findMemberByLidNr($user->getLidnr());

            // The organs that the user belongs to with the correct organId (either 0 or 1)
            $organs = $memberService->getOrgans($member);

            // An array only containing the organ that this member belongs to and with the correct id
            $correctOrgan = array_filter($organs, function (Organ $organ) use ($organId) {
                return $organ->getId() === $organId;
            });

            // Check if the member belongs to the organ
            if (count($correctOrgan) === 0) {
                $translator = $this->getTranslator();
                throw new \User\Permissions\NotAllowedException(
                    $translator->translate('You are not allowed to create an activity for this organ')
                );
            }

            $organ = $correctOrgan[0];
        }

        $activity = new ActivityModel();
        $activity->setBeginTime(new \DateTime($params['beginTime']));
        $activity->setEndTime(new \DateTime($params['endTime']));
        $activity->setSubscriptionDeadline(new \DateTime($params['subscriptionDeadline']));

        if ($dutch ) {
            $activity->setName($params['name']);
            $activity->setLocation($params['location']);
            $activity->setCosts($params['costs']);
            $activity->setDescription($params['description']);
        }
        if ($english) {
            $activity->setNameEn($params['nameEn']);
            $activity->setLocationEn($params['locationEn']);
            $activity->setCostsEn($params['costsEn']);
            $activity->setDescriptionEn($params['descriptionEn']);
        }


        $activity->setCanSignUp($params['canSignUp']);

        // Not user provided input
        $activity->setCreator($user);
        $activity->setOrgan($organ);
        $activity->setStatus(ActivityModel::STATUS_TO_APPROVE);
        $activity->setOnlyGEWIS(true); // Not yet implemented

        if (isset($params['fields'])) {
            foreach ($params['fields'] as $fieldparams){

                $field = $this->createActivityField($fieldparams, $activity, $dutch, $english);
                $em->persist($field);
            }
            $em->flush();
        }

        $em->persist($activity);
        $em->flush();

        return $activity;
    }

    /**
     * Create a new field
     *
     * @pre $params is valid data of Activity\Form\ActivityFieldFieldset
     *
     * @param array $params Parameters for the new field.
     * @param ActivityModel $activity The activity the field belongs to.
     * @param bool $dutch
     * @param bool $english
     * @return \Activity\Model\ActivityField The new field.
     */
    public function createActivityField(array $params, ActivityModel $activity, $dutch, $english)
    {
        assert($dutch || $english, "Activities should have either be in dutch or english");

        $field = new ActivityFieldModel();

        $field->setActivity($activity);
        if ($dutch){
            $field->setName($params['name']);
        }
        if ($english){
            $field->setNameEn($params['nameEn']);
        }
        $field->setType($params['type']);

        if ($params['type'] === '2'){
            $field->setMinimumValue($params['min. value']);
            $field->setMaximumValue($params['max. value']);
        }

        if ($params['type'] === '3'){
            $this->createActivityOptions(
                $field,
                $params,
                $params['optionsEn'] !== '' && $english,
                $params['options'] !== '' && $dutch
            );
        }
        return $field;
    }

    /**
     * Creates options for both languages specified and adds it to $field.
     * If no languages are specified, this method does nothing.
     * @pre The options corresponding to the languages specified are filled in
     * $params. If both languages are specified, they must have the same amount of options.
     *
     * @param ActivityFieldModel $field The field to add the options to.
     * @param array $params The array containing the options strings.
     * @param bool $createEnglishOptions
     * @param bool $createDutchOptions
     */
    protected function createActivityOptions($field, $params, $createEnglishOptions, $createDutchOptions)
    {
        $numOptions = 0;
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        if ($createDutchOptions){
            $options = explode(',', $params['options']);
            $numOptions = count($options);
        }
        if ($createEnglishOptions){
            $optionsEn = explode(',', $params['optionsEn']);
            $numOptions = count($optionsEn);
        }
        for ($i=0; $i<$numOptions; $i++){
            $option = new ActivityOptionModel();
            if ($createDutchOptions){
                $option->setValue($options[$i]);
            }
            if ($createEnglishOptions){
                $option->setValueEn($optionsEn[$i]);
            }
            $option->setField($field);
            $em->persist($option);
        }
        $em->flush();
    }

    /**
     * Approve of an activity
     *
     * @param ActivityModel $activity
     */
    public function approve(ActivityModel $activity)
    {
        if (!$this->isAllowed('approve', 'model')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to change the status of the activity')
            );
        }

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
        if (!$this->isAllowed('reset', 'model')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to change the status of the activity')
            );
        }

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
        if (!$this->isAllowed('disapprove', 'model')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to change the status of the activity')
            );
        }

        $activity->setStatus(ActivityModel::STATUS_DISAPPROVED);
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->persist($activity);
        $em->flush();
    }
}
