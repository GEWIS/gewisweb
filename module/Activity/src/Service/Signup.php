<?php

declare(strict_types=1);

namespace Activity\Service;

use Activity\Form\Signup as SignupForm;
use Activity\Mapper\{
    Signup as SignupMapper,
    SignupFieldValue as SignupFieldValueMapper,
    SignupOption as SignupOptionMapper,
};
use Activity\Model\{
    ExternalSignup as ExternalSignupModel,
    Signup as SignupModel,
    SignupFieldValue as SignupFieldValueModel,
    SignupList as SignupListModel,
    UserSignup as UserSignupModel,
};
use DateTime;
use Doctrine\ORM\{
    EntityManager,
    OptimisticLockException,
    Exception\ORMException,
};
use Laminas\Mvc\I18n\Translator;
use User\Model\User as UserModel;
use User\Permissions\NotAllowedException;

class Signup
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly EntityManager $entityManager,
        private readonly SignupMapper $signupMapper,
        private readonly SignupFieldValueMapper $signupFieldValueMapper,
        private readonly SignupOptionMapper $signupOptionMapper,
    ) {
    }

    /**
     * Return the form for signing up in the preferred language, if available.
     * Otherwise, it returns it in the available language.
     *
     * @param SignupListModel $signupList
     *
     * @return SignupForm
     *
     * @throws NotAllowedException
     */
    public function getForm(SignupListModel $signupList): SignupForm
    {
        if (!$this->aclService->isAllowed('signup', $signupList)) {
            throw new NotAllowedException(
                $this->translator->translate('You need to be logged in to sign up for this activity')
            );
        }

        $form = new SignupForm();
        $form->initialiseForm($signupList);

        return $form;
    }

    /**
     * @param SignupListModel $signupList
     *
     * @return SignupForm
     */
    public function getExternalAdminForm(SignupListModel $signupList): SignupForm
    {
        if (!$this->aclService->isAllowed('adminSignup', $signupList)) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to use the external admin signup')
            );
        }

        $form = new SignupForm();
        $form->initialiseExternalAdminForm($signupList);

        return $form;
    }

    /**
     * @param SignupListModel $signupList
     *
     * @return SignupForm
     */
    public function getExternalForm(SignupListModel $signupList): SignupForm
    {
        if (!$this->aclService->isAllowed('externalSignup', $signupList)) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to use the external signup')
            );
        }

        $form = new SignupForm();
        $form->initialiseExternalForm($signupList);

        return $form;
    }

    /**
     * Gets an array of the signed up users and the associated data.
     *
     * @param SignupListModel $signupList
     *
     * @return array
     */
    public function getSignedUpData(SignupListModel $signupList): array
    {
        if (!$this->aclService->isAllowed('view', $signupList)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the sign up data'));
        }

        $fieldValueMapper = $this->signupFieldValueMapper;
        $result = [];

        foreach ($signupList->getSignUps() as $signup) {
            $entry = [];
            $entry['member'] = $signup->getFullName();
            $entry['values'] = [];

            foreach ($fieldValueMapper->getFieldValuesBySignup($signup) as $fieldValue) {
                // If there is an option type, get the option object as a 'value'.
                $isOption = 3 === $fieldValue->getField()->getType();
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
     * @param SignupListModel $signupList
     * @param UserModel $user
     *
     * @return bool
     */
    public function isSignedUp(
        SignupListModel $signupList,
        UserModel $user,
    ): bool {
        if (!$this->aclService->isAllowed('checkUserSignedUp', 'signupList')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the activities'));
        }

        return $this->signupMapper->isSignedUp($signupList, $user);
    }

    /**
     * Sign a User up for an activity with the specified field values.
     * @param SignupListModel $signupList
     * @param array $fieldResults
     *
     * @return UserSignupModel
     */
    public function signUp(
        SignupListModel $signupList,
        array $fieldResults,
    ): UserSignupModel {
        if (!$this->aclService->isAllowed('signup', 'signupList')) {
            throw new NotAllowedException(
                $this->translator->translate('You need to be logged in to sign up for this activity')
            );
        }

        $user = $this->aclService->getUserIdentityOrThrowException()->getMember();
        $signup = new UserSignupModel();
        $signup->setUser($user);

        return $this->createSignup($signup, $signupList, $fieldResults);
    }

    /**
     * Creates the generic parts of a signup.
     * @template T of ExternalSignupModel|UserSignupModel
     *
     * @param ExternalSignupModel|UserSignupModel $signup
     * @param SignupListModel $signupList
     * @param array $fieldResults
     * @psalm-param T $signup
     *
     * @return ExternalSignupModel|UserSignupModel
     * @psalm-return T
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function createSignup(
        ExternalSignupModel|UserSignupModel $signup,
        SignupListModel $signupList,
        array $fieldResults,
    ): ExternalSignupModel|UserSignupModel {
        $signup->setSignupList($signupList);
        $optionMapper = $this->signupOptionMapper;
        $em = $this->entityManager;

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
                    $fieldValue->setOption($optionMapper->find((int)$value));
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
     * @param string $fullName
     * @param string $email
     * @param array $fieldResults
     *
     * @return ExternalSignupModel
     *
     * @throws NotAllowedException
     * @throws ORMException
     */
    public function adminSignUp(
        SignupListModel $signupList,
        string $fullName,
        string $email,
        array $fieldResults,
    ): ExternalSignupModel {
        if (!($this->aclService->isAllowed('adminSignup', $signupList))) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to subscribe an external user to this sign-up list')
            );
        }

        return $this->manualSignUp($signupList, $fullName, $email, $fieldResults);
    }

    /**
     * Sign an external user up for an activity.
     *
     * @param SignupListModel $signupList
     * @param string $fullName
     * @param string $email
     * @param array $fieldResults
     *
     * @return ExternalSignupModel
     *
     * @throws ORMException
     */
    protected function manualSignUp(
        SignupListModel $signupList,
        string $fullName,
        string $email,
        array $fieldResults,
    ): ExternalSignupModel {
        $signup = new ExternalSignupModel();
        $signup->setEmail($email);
        $signup->setFullName($fullName);

        return $this->createSignup($signup, $signupList, $fieldResults);
    }

    /**
     * Sign an external user up for an activity, allowed by a guest.
     *
     * @param SignupListModel $signupList
     * @param string $fullName
     * @param string $email
     * @param array $fieldResults
     *
     * @return SignupModel
     *
     * @throws NotAllowedException
     * @throws ORMException
     */
    public function externalSignUp(
        SignupListModel $signupList,
        string $fullName,
        string $email,
        array $fieldResults,
    ): SignupModel {
        if (!($this->aclService->isAllowed('externalSignup', $signupList))) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to subscribe to this sign-up list')
            );
        }

        return $this->manualSignUp($signupList, $fullName, $email, $fieldResults);
    }

    /**
     * Undo an activity sign up.
     *
     * @param SignupListModel $signupList
     * @param UserModel $user
     */
    public function signOff(
        SignupListModel $signupList,
        UserModel $user,
    ): void {
        if (!$this->aclService->isAllowed('signoff', 'signupList')) {
            throw new NotAllowedException(
                $this->translator->translate('You need to be logged in to sign off for this activity')
            );
        }

        $signUpMapper = $this->signupMapper;
        $signUp = $signUpMapper->getSignUp($signupList, $user);

        // If the user was not signed up, no need to signoff anyway
        if (is_null($signUp)) {
            return;
        }

        $this->removeSignUp($signUp);
    }

    /**
     * @param SignupModel $signup
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function removeSignUp(SignupModel $signup): void
    {
        $this->signupMapper->remove($signup);
    }

    /**
     * @param SignupListModel $signupList
     *
     * @return int
     */
    public function getNumberOfSubscribedMembers(SignupListModel $signupList): int
    {
        return $this->signupMapper->getNumberOfSignedUpMembers($signupList);
    }

    /**
     * @param ExternalSignupModel $signup
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function externalSignOff(ExternalSignupModel $signup): void
    {
        if (
            !($this->aclService->isAllowed('adminSignup', 'activity') ||
            $this->aclService->isAllowed('adminSignup', $signup->getSignupList()))
        ) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to remove external signups for this activity')
            );
        }

        $this->removeSignUp($signup);
    }

    /**
     * @param DateTime $openDate
     * @param DateTime $closeDate
     *
     * @return bool
     */
    public static function isInSubscriptionWindow(
        DateTime $openDate,
        DateTime $closeDate,
    ): bool {
        $currentTime = new DateTime();

        return $openDate < $currentTime && $currentTime < $closeDate;
    }

    /**
     * Is the currently logged in user allowed to signup.
     *
     * @return bool
     */
    public function isAllowedToSubscribe(): bool
    {
        return $this->aclService->isAllowed('signup', 'signupList');
    }

    /**
     * Is the (guest) user allowed to use the external signup.
     *
     * @return bool
     */
    public function isAllowedToExternalSubscribe(): bool
    {
        return $this->aclService->isAllowed('externalSignup', 'signupList');
    }

    /**
     * @return bool
     */
    public function isAllowedToViewSubscriptions(): bool
    {
        return $this->aclService->isAllowed('view', 'signupList');
    }

    /**
     * @return bool
     */
    public function isAllowedToInternalSubscribe(): bool
    {
        return $this->aclService->isAllowed('signup', 'signupList');
    }
}
