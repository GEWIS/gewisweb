<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivityField as ActivityFieldModel;
use Activity\Model\ActivityOption as ActivityOptionModel;
use Activity\Model\ActivityUpdateProposal as ActivityProposalModel;
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
     * Create an activity from the creation form.
     *
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
            $this->getServiceManager()->get('user_service_user')->getIdentity()
        );

        // Find the organ the activity belongs to, and see if the user has permission to create an activity
        // for this organ. If the id is 0, the activity belongs to no organ.
        $organId = intval($params['organ']);
        $organ = null;
        if ($organId !== 0) {
            $organ = $this->findOrgan($organId);
        }
        $activity = $this->generateActivity($params, $user, $organ, $dutch, $english, ActivityModel::STATUS_TO_APPROVE);

        // Send email to GEFLITST if user checked checkbox of GEFLITST
        if ($activity->getRequireGEFLITST()) {
            $this->getEmailService()->sendEmail('activity_creation_require_GEFLITST', 'email/activity_created_require_GEFLITST',
                'Er is een fotograaf nodig voor een nieuwe GEWIS activiteit | A GEWIS activity needs a photographer of GEFLITST',
                ['activity' => $activity]);


        }

        return $activity;
    }

    /**
     * Create a new update proposal from user form
     *
     * @param ActivityModel $oldActivity
     * @param array $params
     * @param type $dutch
     * @param type $english
     * @return ActivityProposalModel
     * @return bool indicating whether the update was applied or is pending
     */
    public function createUpdateProposal(ActivityModel $oldActivity, array $params, $dutch, $english)
    {
        if (!($this->isAllowed('update', 'activity') ||
                $this->isAllowed('update', $oldActivity))) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to update this activity')
            );
        }
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        $user = $this->getServiceManager()->get('user_service_user')->getIdentity();
        $newActivity = $this->generateActivity(
            $params,
            $user,
            $oldActivity->getOrgan(),
            $dutch,
            $english,
            ActivityModel::STATUS_UPDATE
        );

        $oldProposalContainer = $oldActivity->getUpdateProposal();

        if ($oldProposalContainer->count() !== 0) {
            $oldProposal = $oldProposalContainer->unwrap()->first();
            //Remove old update proposal
            $oldUpdate = $oldProposal->getNew();
            $oldProposal->setNew($newActivity);
            $em->remove($oldUpdate);
            $em->flush();

            if ($this->canApplyUpdateProposal($oldActivity)) {
                $this->updateActivity($oldProposal);
                return true;
            }

            return false;
        }

        $proposal = new \Activity\Model\ActivityUpdateProposal();
        $proposal->setOld($oldActivity);
        $proposal->setNew($newActivity);
        $em->persist($proposal);
        $em->flush();

        if ($this->canApplyUpdateProposal($oldActivity)) {
            $this->updateActivity($proposal);
            return true;
        }

        return false;
    }

    /**
     * Checks whether the current user is allowed to apply an update proposal for the given activity
     *
     * @param ActivityModel $activity
     * @return bool indicating whether the update may be applied
     */
    protected function canApplyUpdateProposal(ActivityModel $activity)
    {
        if ($this->isAllowed('update', 'activity')) {
            return true;
        }

        if (!$this->isAllowed('update', $activity)) {
            return false;
        }

        // If the activity has not been approved yet the update proposal can be applied
        return $activity->getStatus() !== ActivityModel::STATUS_APPROVED;
    }

    /**
     * Apply a proposed activity update
     *
     * @param ActivityProposalModel $proposal
     */
    public function updateActivity(ActivityProposalModel $proposal)
    {
        $old = $proposal->getOld();
        $new = $proposal->getNew();
        $this->copyActivity($old, $new);
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        $em->remove($proposal);//Proposal is no longer needed.
        $em->remove($new);
        $em->flush();
    }

    /**
     * Copies all relevant activity attributes from $new to $old
     *
     * @param ActivityModel $old
     * @param ActivityModel $new
     */
    protected function copyActivity(ActivityModel $old, ActivityModel $new)
    {
        $old->setName($new->getName());
        $old->setNameEn($new->getNameEn());
        $old->setBeginTime($new->getBeginTime());
        $old->setEndTime($new->getEndTime());
        $old->setSubscriptionDeadline($new->getSubscriptionDeadline());
        $old->setLocation($new->getLocation());
        $old->setLocationEn($new->getLocationEn());
        $old->setCosts($new->getCosts());
        $old->setCostsEn($new->getCostsEn());
        $old->setDescription($new->getDescription());
        $old->setDescriptionEn($new->getDescriptionEn());
        $old->setCreator($new->getCreator());
        $old->setCanSignUp($new->getCanSignUp());
        $old->setOnlyGEWIS($new->getOnlyGEWIS());
    }

    /**
     * Revoke a proposed activity update
     * NB: This action permanently removes the proposal, so this cannot be undone.
     * (The potentially updated activity remains untouched)
     *
     * @param ActivityProposalModel $proposal
     */
    public function revokeUpdateProposal(ActivityProposalModel $proposal)
    {
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $new = $proposal->getNew();
        $em->remove($proposal);
        $em->remove($new);
        $em->flush();
    }

    /**
     * Create an activity from parameters.
     *
     * @pre $params is valid data of Activity\Form\Activity
     *
     * @param array $params Parameters describing activity
     * @param User $user The user that creates this activity
     * @param Organ $organ The organ this activity is associated with
     * @param boolean $dutch Whether dutch information is provided
     * @param boolean $english Whether english information is provided
     *
     * @return ActivityModel Activity that was created.
     */
    protected function generateActivity(array $params, $user, $organ, $dutch, $english, $initialStatus)
    {
        $activity = new ActivityModel();
        $activity->setBeginTime(new \DateTime($params['beginTime']));
        $activity->setEndTime(new \DateTime($params['endTime']));
        //Default to the endtime if no deadline was set (so there is no deadline effectively)
        $activity->setSubscriptionDeadline(
            empty($params['subscriptionDeadline']) ?
                $activity->getEndTime() :
                new \DateTime($params['subscriptionDeadline'])
        );

        $this->setLanguageSpecificParameters($activity, $params, $dutch, $english);
        $activity->setCanSignUp($params['canSignUp']);
        $activity->setIsFood($params['isFood']);
        $activity->setIsMyFuture($params['isMyFuture']);
        $activity->setRequireGEFLITST($params['requireGEFLITST']);
        $activity->setOnlyGEWIS($params['onlyGEWIS']);
        $activity->setDisplaySubscribedNumber($params['displaySubscribedNumber']);

        // Not user provided input
        $activity->setCreator($user);
        $activity->setOrgan($organ);
        $activity->setStatus($initialStatus);

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        if (isset($params['fields'])) {
            foreach ($params['fields'] as $fieldparams) {

                $field = $this->createActivityField($fieldparams, $activity, $dutch, $english);
                $em->persist($field);
            }
            $em->flush();
        }

        $em->persist($activity);
        $em->flush();

        // Send an email when a new Activity was created.
        $this->getEmailService()->sendEmail('activity_creation', 'email/activity',
            'Nieuwe activiteit aangemaakt op de GEWIS website | New activity was created on the GEWIS website',
            ['activity' => $activity]);

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

        if (!$organService->canEditOrgan($organ)) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity for this organ')
            );
        }

        return $organ;
    }

    /**
     * Set the language specific (dutch and english) parameters of an activity
     *
     * @param type $activity
     * @param type $params
     * @param type $dutch
     * @param type $english
     */
    protected function setLanguageSpecificParameters(ActivityModel $activity, $params, $dutch, $english)
    {
        if ($dutch) {
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
        if ($dutch) {
            $field->setName($params['name']);
        }
        if ($english) {
            $field->setNameEn($params['nameEn']);
        }
        $field->setType($params['type']);

        if ($params['type'] === '2') {
            $field->setMinimumValue($params['min. value']);
            $field->setMaximumValue($params['max. value']);
        }

        if ($params['type'] === '3') {
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

        if ($createDutchOptions) {
            $options = explode(',', $params['options']);
            $numOptions = count($options);
        }
        if ($createEnglishOptions) {
            $optionsEn = explode(',', $params['optionsEn']);
            $numOptions = count($optionsEn);
        }
        for ($i = 0; $i < $numOptions; $i++) {
            $option = new ActivityOptionModel();
            if ($createDutchOptions) {
                $option->setValue($options[$i]);
            }
            if ($createEnglishOptions) {
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

    /**
     * Get the email service.
     *
     * @return \Application\Service\Email
     */
    public function getEmailService()
    {
        return $this->sm->get('application_service_email');
    }
}
