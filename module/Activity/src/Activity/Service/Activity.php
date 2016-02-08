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
     * Return activity creation form
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
        if ($organId !== 0){
            $organ = $this->findOrgan($organId);
        }
        $activity = new ActivityModel();
        $activity->setBeginTime(new \DateTime($params['beginTime']));
        $activity->setEndTime(new \DateTime($params['endTime']));
        //Default to the endtime if no deadline was set (so there is no deadline effectively)
        $activity->setSubscriptionDeadline(
            empty($params['subscriptionDeadline']) ?
            $activity->getEndTime() :
            new \DateTime($params['subscriptionDeadline'])
        );

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
     * Find the organ the activity belongs to, and see if the user has permission to create an activity
     * for this organ.
     *
     * @param int $organId The id of the organ associated with the activity
     * @return Organ The organ associated with the activity, if the user is a member of that organ
     * @throws \User\Permissions\NotAllowedException if the user is not a member of the organ specified
     */
    protected function findOrgan($organId)
    {
        $organService = $this->getServiceManager()->get('decision_service_organ');
        $organ = $organService->getOrgan($organId);

        if (!$organService->canEditOrgan($organ)){
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity for this organ')
            );
        }
        return $organ;
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


    /**
     * Get the activity mapper.
     *
     * @return \Activity\Mapper\Activity
     */
    public function getActivityMapper()
    {
        return $this->sm->get('activity_mapper_activity');
    }
}
