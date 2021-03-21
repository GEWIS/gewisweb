<?php

namespace Activity\Service;

use Activity\Mapper\ActivityFieldValue;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\ExternalSignup as ExternalSignupModel;
use Activity\Model\Signup as SignupModel;
use Activity\Model\SignupFieldValue as SignupFieldValueModel;
use Activity\Model\SignupList as SignupListModel;
use Activity\Model\UserSignup as UserSignupModel;
use Application\Service\AbstractAclService;
use DateTime;
use Decision\Model\Member;
use User\Permissions\NotAllowedException;
use Zend\Permissions\Acl\Acl;

class Signup extends AbstractAclService
{
    /**
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('activity_acl');
    }

    /**
     * Return the form for signing up in the preferred language, if available.
     * Otherwise, it returns it in the available language.
     *
     * @param SignupListModel $signupList
     * @return \Activity\Form\Signup
     * @throws NotAllowedException
     */
    public function getForm($signupList)
    {
        if (!$this->isAllowed('signup', $signupList)) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You need to be logged in to sign up for this activity')
            );
        }

        $form = new \Activity\Form\Signup();
        $form->initialiseForm($signupList);

        return $form;
    }

    public function getExternalAdminForm($signupList)
    {
        if (!$this->isAllowed('adminSignup', $signupList)) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to use the external admin signup')
            );
        }

        $form = new \Activity\Form\Signup();
        $form->initialiseExternalAdminForm($signupList);

        return $form;
    }

    public function getExternalForm($signupList)
    {
        if (!$this->isAllowed('externalSignup', $signupList)) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to use the external signup')
            );
        }

        $form = new \Activity\Form\Signup();
        $form->initialiseExternalForm($signupList);

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
        if (!$this->isAllowed('view', 'signupList')) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
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
    public function getSignedUpData(SignupListModel $signupList)
    {
        if (!$this->isAllowed('view', $signupList)) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to view the sign up data')
            );
        }

        $fieldValueMapper = $this->getServiceManager()->get('activity_mapper_signup_field_value');
        $result = [];

        foreach ($signupList->getSignUps() as $signup) {
            $entry = [];
            $entry['member'] = $signup->getFullName();
            $entry['values'] = [];

            foreach ($fieldValueMapper->getFieldValuesBySignup($signup) as $fieldValue) {
                // If there is an option type, get the option object as a 'value'.
                $isOption = $fieldValue->getField()->getType() === 3;
                $value = $isOption ? $fieldValue->getOption() : $fieldValue->getValue();
                $entry['values'][$fieldValue->getField()->getId()] = $value;
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Gets an array of the signed up users, but without the associated data.
     *
     * @return array
     */
    public function getSignedUpDataWithoutFields(SignupListModel $signupList)
    {
        if (!$this->isAllowed('view', $signupList)) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to view the sign up data')
            );
        }

        $result = [];

        foreach ($signupList->getSignUps() as $signup) {
            $entry = [];
            $entry['fullName'] = $signup->getFullName();
            $entry['email'] = $signup->getEmail();

            $entry['type'] = $this->getTranslator()->translate('External');

            if ($signup instanceof Activity\Model\UserSignup) {
                $entry['type'] = $this->getTranslator()->translate('User');
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Check if a member is signed up for an activity.
     *
     * @param ActivityModel $activity
     * @param Member $user
     *
     * @return bool
     */
    public function isSignedUp($activity, Member $user)
    {
        if (!$this->isAllowed('checkUserSignedUp', 'signupList')) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
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
        if (!$this->isAllowed('checkUserSignedUp', 'signupList')) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to view activities which you signed up for')
            );
        }
        $user = $this->getServiceManager()->get('user_service_user')->getIdentity();
        $activitySignups = $this->getServiceManager()->get('activity_mapper_signup')->getSignedUpActivities(
            $user->getLidnr()
        );
        $activities = [];
        foreach ($activitySignups as $activitySignup) {
            $activities[] = $activitySignup->getActivity()->getId();
        }
        return $activities;
    }

    /**
     * Sign a User up for an activity with the specified field values.
     *
     * @param SignupListModel $signupList
     * @param array $fieldResults
     */
    public function signUp(SignupListModel $signupList, array $fieldResults)
    {
        if (!$this->isAllowed('signup', 'signupList')) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You need to be logged in to sign up for this activity')
            );
        }

        $user = $this->getServiceManager()->get('user_service_user')->getIdentity();
        $signup = new UserSignupModel();
        $signup->setUser($user);
        $this->createSignup($signup, $signupList, $fieldResults);
    }

    /**
     * Creates the generic parts of a signup.
     *
     * @param SignupModel $signup
     * @param SignupListModel $activity
     * @param array $fieldResults
     *
     * @return SignupModel
     */
    protected function createSignup(SignupModel $signup, SignupListModel $signupList, array $fieldResults)
    {
        $signup->setSignupList($signupList);
        $optionMapper = $this->getServiceManager()->get('activity_mapper_signup_option');
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        foreach ($signupList->getFields() as $field) {
            $fieldValue = new SignupFieldValueModel();
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
     * Sign an external user up for an activity, which the current user may admin.
     *
     * @param SignupListModel $signupList
     * @param type $fullName
     * @param type $email
     * @param array $fieldResults
     * @throws NotAllowedException
     */
    public function adminSignUp(SignupListModel $signupList, $fullName, $email, array $fieldResults)
    {
        if (!($this->isAllowed('adminSignup', $signupList))) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to subscribe an external user to this sign-up list')
            );
        }

        $this->manualSignUp($signupList, $fullName, $email, $fieldResults);
    }

    /**
     * Sign an external user up for an activity.
     *
     * @param SignupListModel $signupList
     * @param string $fullName
     * @param string $email
     * @param array $fieldResults
     *
     * @throws NotAllowedException
     */
    protected function manualSignUp(SignupListModel $signupList, $fullName, $email, array $fieldResults)
    {
        $signup = new ExternalSignupModel();
        $signup->setEmail($email);
        $signup->setFullName($fullName);
        $this->createSignup($signup, $signupList, $fieldResults);
    }

    /**
     * Sign an external user up for an activity, allowed by a guest.
     *
     * @param SignupListModel $signupList
     * @param type $fullName
     * @param type $email
     * @param array $fieldResults
     * @throws NotAllowedException
     */
    public function externalSignUp(SignupListModel $signupList, $fullName, $email, array $fieldResults)
    {
        if (!($this->isAllowed('externalSignup', $signupList))) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to subscribe to this sign-up list')
            );
        }

        $this->manualSignUp($signupList, $fullName, $email, $fieldResults);
    }

    /**
     * Undo an activity sign up.
     *
     * @param SignupListModel $signupList
     * @param Member $user
     */
    public function signOff(SignupListModel $signupList, Member $user)
    {
        if (!$this->isAllowed('signoff', 'signupList')) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You need to be logged in to sign off for this activity')
            );
        }

        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');
        $signUp = $signUpMapper->getSignUp($signupList->getId(), $user->getLidnr());

        // If the user was not signed up, no need to signoff anyway
        if (is_null($signUp)) {
            return;
        }

        $this->removeSignUp($signUp);
    }

    protected function removeSignUp(SignupModel $signup)
    {
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $em->remove($signup);
        $em->flush();
    }

    public function getNumberOfSubscribedMembers(SignupListModel $signupList)
    {
        return $this->getServiceManager()->get('activity_mapper_signup')
            ->getNumberOfSignedUpMembers($signupList->getId())[1];
    }

    public function externalSignOff(ExternalSignupModel $signup)
    {
        if (!($this->isAllowed('adminSignup', 'activity') ||
            $this->isAllowed('adminSignup', $signup->getActivity()))) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to remove external signups for this activity')
            );
        }
        $this->removeSignUp($signup);
    }

    public function isInSubscriptionWindow($openDate, $closeDate)
    {
        $currentTime = new DateTime();
        return $openDate < $currentTime && $currentTime < $closeDate;
    }

    /**
     * Is the currently logged in user allowed to signup
     *
     * @return bool
     */
    public function isAllowedToSubscribe()
    {
        return $this->isAllowed('signup', 'signupList');
    }

    /**
     * Is the (guest) user allowed to use the external signup
     *
     * @return bool
     */
    public function isAllowedToExternalSubscribe()
    {
        return $this->isAllowed('externalSignup', 'signupList');
    }

    public function isAllowedToViewSubscriptions()
    {
        return $this->isAllowed('view', 'signupList');
    }

    public function isAllowedToInternalSubscribe()
    {
        return $this->isAllowed('signup', 'signupList');
    }

    /**
     * @return ActivityFieldValue
     */
    public function getActivityFieldValueMapper()
    {
        return $this->getServiceManager()->get('activity_mapper_activity_field_value');
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
        return 'signupList';
    }
}
