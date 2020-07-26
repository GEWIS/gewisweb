<?php

namespace Activity\Service;

use Activity\Model\LocalisedText;
use Application\Service\AbstractAclService;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\SignupField as SignupFieldModel;
use Activity\Model\SignupList as SignupListModel;
use Activity\Model\SignupOption as SignupOptionModel;
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
    public function getActivityForm()
    {
        if (!$this->isAllowed('create', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity')
            );
        }

        return $this->getServiceManager()->get('activity_form_activity');
    }

    public function getSignupListForm()
    {
        return $this->getServiceManager()->get('activity_form_signuplist');
    }

    public function getSignupListFieldsForm()
    {
        return $this->getServiceManager()->get('activity_form_signuplist_fields');
    }

    /**
     * Create an activity from the creation form.
     *
     * @pre $params is valid data of Activity\Form\Activity
     *
     * @param array $data Parameters describing activity
     *
     * @return ActivityModel Activity that was created.
     */
    public function createActivity($data)
    {
        if (!$this->isAllowed('create', 'activity')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create an activity')
            );
        }

        // Find the creator
        $user = $this->getServiceManager()->get('user_service_user')->getIdentity();

        // Find the organ the activity belongs to, and see if the user has permission to create an activity
        // for this organ. If the id is 0, the activity belongs to no organ.
        $organId = intval($data['organ']);
        $organ = null;

        if ($organId !== 0) {
            $organ = $this->findOrgan($organId);
        }

        $activity = $this->saveActivityData($data, $user, $organ, ActivityModel::STATUS_TO_APPROVE);

        // Send email to GEFLITST if user checked checkbox of GEFLITST
        if ($activity->getRequireGEFLITST()) {
            $this->requestGEFLITST($activity, $user, $organ);
        }

        return $activity;
    }

    /**
     * @param $activity ActivityModel
     * @param $user \User\Model\User
     * @param $organ Organ
     */
    private function requestGEFLITST($activity, $user, $organ)
    {
        // Default to an English title, otherwise use the Dutch title
        $activityTitle = $activity->getName()->getText('en');
        $activityTime = $activity->getBeginTime()->format('d-m-Y H:i');

        $type = 'activity_creation_require_GEFLITST';
        $view = 'email/activity_created_require_GEFLITST';

        if ($organ != null) {
            $subject = sprintf('%s: %s on %s', $organ->getAbbr(), $activityTitle, $activityTime);

            $organInfo = $organ->getApprovedOrganInformation();
            if ($organInfo != null && $organInfo->getEmail() != null) {
                $this->getEmailService()->sendEmailAsOrgan($type, $view, $subject,
                    ['activity' => $activity, 'requester' => $organ->getName()], $organInfo);
            } else {
                // The organ did not fill in it's email address, so send the email as the requested user.
                $this->getEmailService()->sendEmailAsUser($type, $view, $subject,
                    ['activity' => $activity, 'requester' => $organ->getName()], $user);
            }
        } else {
            $subject = sprintf('Member Initiative: %s on %s', $activityTitle, $activityTime);

            $this->getEmailService()->sendEmailAsUser($type, $view, $subject,
                ['activity' => $activity, 'requester' => $user->getMember()->getFullName()], $user);
        }
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
    public function createUpdateProposal(ActivityModel $oldActivity, array $data)
    {
        if (!$this->isAllowed('update', $oldActivity)) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to update this activity')
            );
        }

        $user = $this->getServiceManager()->get('user_service_user')->getIdentity();
        $newActivity = $this->saveActivityData(
            $data,
            $user,
            $oldActivity->getOrgan(),
            ActivityModel::STATUS_UPDATE
        );

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
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
        $old->setBeginTime($new->getBeginTime());
        $old->setEndTime($new->getEndTime());
        $old->setSubscriptionDeadline($new->getSubscriptionDeadline());
        $old->setLocation($new->getLocation());
        $old->setCosts($new->getCosts());
        $old->setDescription($new->getDescription());
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
     * @pre $data is valid data of Activity\Form\Activity
     *
     * @param array $data Parameters describing activity
     * @param User $user The user that creates this activity
     * @param Organ $organ The organ this activity is associated with
     *
     * @return ActivityModel Activity that was created.
     */
    protected function saveActivityData($data, $user, $organ, $status)
    {
        $activity = new ActivityModel();
        $activity->setBeginTime(new \DateTime($data['beginTime']));
        $activity->setEndTime(new \DateTime($data['endTime']));

        $activity->setName(new LocalisedText($data['nameEn'], $data['name']));
        $activity->setLocation(new LocalisedText($data['locationEn'], $data['location']));
        $activity->setCosts(new LocalisedText($data['costsEn'], $data['costs']));
        $activity->setDescription(new LocalisedText($data['descriptionEn'], $data['description']));

        $activity->setIsMyFuture($data['isMyFuture']);
        $activity->setRequireGEFLITST($data['requireGEFLITST']);

        // Not user provided input
        $activity->setCreator($user);
        $activity->setOrgan($organ);
        $activity->setStatus($status);

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        if (isset($data['signuplists'])) {
            foreach ($data['signuplists'] as $signupList) {
                $signupList = $this->createSignupList($signupList, $activity);
                $em->persist($signupList);
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
     * Creates a SignupList for the specified Activity.
     *
     * @param array $data
     * @param \Activity\Model\Activity $activity
     * @return \Activity\Model\SignupList
     */
    public function createSignupList($data, $activity)
    {
        $signupList = new SignupListModel();

        $signupList->setActivity($activity);
        $signupList->setName(new LocalisedText($data['nameEn'], $data['name']));
        $signupList->setOpenDate(new \DateTime($data['openDate']));
        $signupList->setCloseDate(new \DateTime($data['closeDate']));

        $signupList->setOnlyGEWIS($data['onlyGEWIS']);
        $signupList->setDisplaySubscribedNumber($data['displaySubscribedNumber']);

        if (isset($data['fields'])) {
            $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

            foreach ($data['fields'] as $field) {
                $field = $this->createSignupField($field, $signupList);
                $em->persist($field);
            }
            $em->flush();
        }

        return $signupList;
    }

    /**
     * Create a new field
     *
     * @pre $data is valid data of Activity\Form\SignupListFields
     *
     * @param array $data Parameters for the new field.
     * @param \Activity\Model\SignupList $activity The SignupList the field belongs to.
     *
     * @return \Activity\Model\ActivityField The new field.
     */
    public function createSignupField($data, $signupList)
    {
        $field = new SignupFieldModel();

        $field->setSignupList($signupList);
        $field->setName(new LocalisedText($data['nameEn'], $data['name']));
        $field->setType($data['type']);

        if ($data['type'] === '2') {
            $field->setMinimumValue($data['min. value']);
            $field->setMaximumValue($data['max. value']);
        }

        if ($data['type'] === '3') {
            $this->createSignupOption($data, $field);
        }

        return $field;
    }

    /**
     * Creates options for both languages specified and adds it to $field.
     * If no languages are specified, this method does nothing.
     * @pre The options corresponding to the languages specified are filled in
     * $params. If both languages are specified, they must have the same amount of options.
     *
     * @param array $data The array containing the options strings.
     * @param \Activity\Model\SignupField $field The field to add the options to.
     */
    protected function createSignupOption($data, $field)
    {
        $numOptions = 0;
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        if (isset($data['options'])) {
            $options = explode(',', $data['options']);
            $options = array_map('trim', $options);
            $numOptions = count($options);
        }

        if (isset($data['optionsEn'])) {
            $optionsEn = explode(',', $data['optionsEn']);
            $optionsEn = array_map('trim', $optionsEn);
            $numOptions = count($optionsEn);
        }

        for ($i = 0; $i < $numOptions; $i++) {
            $option = new SignupOptionModel();
            $option->setValue(new LocalisedText(
                isset($data['optionsEn']) ? $optionsEn[$i] : null,
                isset($data['options']) ? $options[$i] : null
            ));
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
