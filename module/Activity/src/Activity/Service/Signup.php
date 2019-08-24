<?php

namespace Activity\Service;

use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivitySignup;
use Activity\Model\UserActivitySignup;
use Activity\Model\ExternalActivitySignup;
use Application\Service\AbstractAclService;
use Decision\Model\Member;

class Signup extends AbstractAclService
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
        return 'activitySignup';
    }

    /**
     * Return the form for signing up in the preferred language, if available.
     * Otherwise, it returns it in the available language.
     *
     * @param type $fields
     * @param bool $external Whether the signup is external.
     * @return type
     * @throws \User\Permissions\NotAllowedException
     */
    public function getForm($fields)
    {
        if (!$this->isAllowed('signup', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need to be logged in to sign up for this activity')
            );
        }
        $form = new \Activity\Form\ActivitySignup();
        $form->initialiseForm($fields);
        return $form;
    }

    public function getExternalAdminForm($fields)
    {
        $form = new \Activity\Form\ActivitySignup();
        $form->initialiseExternalAdminForm($fields);
        return $form;
    }

    public function getExternalForm($fields)
    {
        if (!$this->isAllowed('externalSignup', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to use the external signup')
            );
        }
        $form = new \Activity\Form\ActivitySignup();
        $form->initialiseExternalForm($fields);
        return $form;
    }

    /**
     * Get a list of all the members that are signed up for an activity.
     *
     * @param ActivityModel $activity
     *
     * @return array
     */
    public function getSignedUpUsers($activity)
    {
        if (!$this->isAllowed('view', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view who is signed up for this activity')
            );
        }

        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');
        return $signUpMapper->getSignedUp($activity->getId());
    }

    /**
     * Gets an array of the signed up users and the associated data
     *
     * @return array
     */
    public function getSignedUpData(ActivityModel $activity)
    {
        if (!$this->isAllowed('view', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the sign up data')
            );
        }

        $fieldValueMapper = $this->getServiceManager()->get('activity_mapper_activity_field_value');
        $result = [];
        foreach($activity->getSignUps() as $signup){
            $entry = [];
            $entry['member'] = $signup->getFullName();
            $entry['values'] = [];
            foreach($fieldValueMapper->getFieldValuesBySignup($signup) as $fieldValue){
                //If there is an option type, get the option object as a 'value'.
                $isOption = $fieldValue->getField()->getType() === 3;
                $value = $isOption ? $fieldValue->getOption() : $fieldValue->getValue();
                $entry['values'][$fieldValue->getField()->getId()] = $value;
            }
            $result[] = $entry;
        }
        return $result;
    }

    /**
     * Check if a member is signed up for an activity.
     *
     * @param ActivityModel          $activity
     * @param \Decision\Model\Member $user
     *
     * @return bool
     */
    public function isSignedUp($activity, Member $user)
    {
        if (!$this->isAllowed('checkUserSignedUp', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the activities')
            );
        }

        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');

        return $signUpMapper->isSignedUp($activity->getId(), $user->getLidnr());
    }

    /**
     * Get the ids of all activities which the current user is signed up for.
     *
     * @return array
     */
    public function getSignedUpActivityIds()
    {
        if (!$this->isAllowed('checkUserSignedUp', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view activities which you signed up for')
            );
        }
        $user = $this->getServiceManager()->get('user_service_user')->getIdentity();
        $activitySignups = $this->getServiceManager()->get('activity_mapper_signup')->getSignedUpActivities(
            $user->getLidnr()
        );
        $activities = [];
        foreach($activitySignups as $activitySignup) {
            $activities[] = $activitySignup->getActivity()->getId();
        }
        return $activities;
    }

    /**
     * Sign a User up for an activity with the specified field values.
     *
     * @param ActivityModel $activity
     * @param array $fieldResults
     */
    public function signUp(ActivityModel $activity, array $fieldResults)
    {
        if (!$this->isAllowed('signup', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need to be logged in to sign up for this activity')
            );
        }
        $user = $this->getServiceManager()->get('user_service_user')->getIdentity();
        $signup = new UserActivitySignup();
        $signup->setUser($user);
        $this->createSignup($signup, $activity, $fieldResults);
    }

    /**
     * Sign an external user up for an activity, which the current user may admin.
     *
     * @param \Activity\Service\AcitivityModel $activity
     * @param type $fullName
     * @param type $email
     * @param array $fieldResults
     * @throws \User\Permissions\NotAllowedException
     */
    public function adminSignUp(ActivityModel $activity, $fullName, $email, array $fieldResults)
    {
        if (!($this->isAllowed('adminSignup', 'activity') ||
                $this->isAllowed('adminSignup', $activity))) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create signups for this activity')
            );
        }
        $this->manualSignUp($activity, $fullName, $email, $fieldResults);
    }

    /**
     * Sign an external user up for an activity, allowed by a guest.
     *
     * @param \Activity\Service\AcitivityModel $activity
     * @param type $fullName
     * @param type $email
     * @param array $fieldResults
     * @throws \User\Permissions\NotAllowedException
     */
    public function externalSignUp(ActivityModel $activity, $fullName, $email, array $fieldResults)
    {
        if (!($this->isAllowed('externalSignup', 'activitySignup'))) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to create signups for this activity')
            );
        }
        $this->manualSignUp($activity, $fullName, $email, $fieldResults);
    }

    /**
     * Sign an external user up for an activity.
     *
     * @param \Activity\Service\AcitivityModel $activity
     * @param type $user
     * @param array $fieldResults
     * @throws \User\Permissions\NotAllowedException
     */
    protected function manualSignUp(ActivityModel $activity, $fullName, $email, array $fieldResults)
    {
        $signup = new ExternalActivitySignup();
        $signup->setEmail($email);
        $signup->setFullName($fullName);
        $this->createSignup($signup, $activity, $fieldResults);
    }

    /**
     * Creates the generic parts of a signup.
     * @param ActivitySignup $signup
     * @param ActivityModel $activity
     * @param type $user
     * @param array $fieldResults
     * @return ActivitySignup
     */
    protected function createSignup(ActivitySignup $signup, ActivityModel $activity, array $fieldResults)
    {
        $signup->setActivity($activity);
        $optionMapper = $this->getServiceManager()->get('activity_mapper_activity_option');
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        foreach ($activity->getFields() as $field){
            $fieldValue = new \Activity\Model\ActivityFieldValue();
            $fieldValue->setField($field);
            $value = $fieldResults[$field->getId()];

            //Change the value into the actual format
            switch ($field->getType()) {
                case 0://'Text'
                case 2://'Number'
                    $fieldValue->setValue($value);
                    break;
                case 1://'Yes/No'
                    $fieldValue->setValue(($value) ? 'Yes' : 'No');
                    break;
                case 3://'Choice'
                    $fieldValue->setOption($optionMapper->getOptionById((int)$value));
                    break;
            }
            $fieldValue->setSignup($signup);
            $em->persist($fieldValue);
        }
        $em->persist($signup);
        $em->flush();
        return $signup;
    }

    /**
     * Undo an activity sign up.
     *
     * @param ActivityModel $activity
     * @param Member        $user
     */
    public function signOff(ActivityModel $activity, Member $user)
    {
        if (!$this->isAllowed('signoff', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need to be logged in to sign off for this activity')
            );
        }

        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');
        $signUp = $signUpMapper->getSignUp($activity->getId(), $user->getLidnr());

        // If the user was not signed up, no need to signoff anyway
        if (is_null($signUp)) {
            return;
        }
        $this->removeSignUp($signUp);
    }

    public function getNumberOfSubscribedMembers(ActivityModel $activity)
    {
        return $this->getServiceManager()->get('activity_mapper_signup')
                ->getNumberOfSignedUpMembers($activity->getId())[1];
    }

    public function externalSignOff(ExternalActivitySignup $signup)
    {
        if (!($this->isAllowed('adminSignup', 'activity') ||
                $this->isAllowed('adminSignup', $signup->getActivity()))) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to remove external signups for this activity')
            );
        }
        $this->removeSignUp($signup);
    }

    protected function removeSignUp(ActivitySignup $signup)
    {
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->remove($signup);
        $em->flush();
    }

    /**
     * Is the currently logged in user allowed to signup
     *
     * @return bool
     */
    public function isAllowedToSubscribe()
    {
        return $this->isAllowed('signup', 'activitySignup');
    }

    /**
     * Is the (guest) user allowed to use the external signup
     *
     * @return bool
     */
    public function isAllowedToExternalSubscribe()
    {
        return $this->isAllowed('externalSignup', 'activitySignup');
    }

    public function isAllowedToViewSubscriptions()
    {
        return $this->isAllowed('view', 'activitySignup');
    }

    public function isAllowedToInternalSubscribe()
    {
        return $this->isAllowed('signup', 'activitySignup');
    }
    /**
     * @return \Activity\Mapper\ActivityFieldValue
     */
    public function getActivityFieldValueMapper(){

        return $this->getServiceManager()->get('activity_mapper_activity_field_value');
    }
}
