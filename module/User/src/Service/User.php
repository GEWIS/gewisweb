<?php

namespace User\Service;

use DateInterval;
use DateTime;
use Decision\Mapper\Member;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Mvc\I18n\Translator;
use RuntimeException;
use User\Authentication\Adapter\Mapper;
use User\Authentication\AuthenticationService;
use User\Form\Activate;
use User\Form\Login;
use User\Form\Password;
use User\Form\Register as RegisterForm;
use User\Mapper\NewUser;
use User\Model\NewUser as NewUserModel;
use User\Model\User as UserModel;
use User\Permissions\NotAllowedException;

/**
 * User service.
 */
class User
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Bcrypt
     */
    private $bcrypt;

    /**
     * @var AuthenticationService
     * with regular Mapper adapter
     */
    private $authService;

    /**
     * @var AuthenticationService
     * with PinMapper adapter
     */
    private $pinAuthService;

    /**
     * @var Email
     */
    private $emailService;

    /**
     * @var \User\Mapper\User
     */
    private $userMapper;

    /**
     * @var NewUser
     */
    private $newUserMapper;

    /**
     * @var Member
     */
    private $memberMapper;

    /**
     * @var RegisterForm
     */
    private $registerForm;

    /**
     * @var Activate
     */
    private $activateForm;

    /**
     * @var Login
     */
    private $loginForm;

    /**
     * @var Password
     */
    private $passwordForm;

    private AclService $aclService;

    public function __construct(
        Translator $translator,
        Bcrypt $bcrypt,
        AuthenticationService $authService,
        AuthenticationService $pinAuthService,
        Email $emailService,
        \User\Mapper\User $userMapper,
        NewUser $newUserMapper,
        Member $memberMapper,
        RegisterForm $registerForm,
        Activate $activateForm,
        Login $loginForm,
        Password $passwordForm,
        AclService $aclService
    ) {
        $this->translator = $translator;
        $this->bcrypt = $bcrypt;
        $this->authService = $authService;
        $this->pinAuthService = $pinAuthService;
        $this->emailService = $emailService;
        $this->userMapper = $userMapper;
        $this->newUserMapper = $newUserMapper;
        $this->memberMapper = $memberMapper;
        $this->registerForm = $registerForm;
        $this->activateForm = $activateForm;
        $this->loginForm = $loginForm;
        $this->passwordForm = $passwordForm;
        $this->aclService = $aclService;
    }

    /**
     * Activate a user.
     *
     * @param array $data activation data
     * @param NewUserModel $newUser The user to create
     *
     * @return bool
     */
    public function activate($data, NewUserModel $newUser)
    {
        $form = $this->activateForm;

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        // first try to obtain the user
        $user = $this->userMapper->findByLidnr($newUser->getLidnr());
        if (null === $user) {
            // create a new user from this data, and insert it into the database
            $user = new UserModel($newUser);
        }

        $user->setPassword($this->bcrypt->create($data['password']));

        // this will also save a user with a lost password
        $this->userMapper->createUser($user, $newUser);

        return true;
    }

    /**
     * Register a user.
     *
     * Will also send an email to the user.
     *
     * @param array $data Registration data
     *
     * @return NewUserModel New registered user. Null when the user could not be registered.
     */
    public function register($data)
    {
        $form = $this->registerForm;
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // get the member
        $data = $form->getData();
        $member = $this->memberMapper->findByLidnr($data['lidnr']);

        if (null === $member) {
            $form->setError(RegisterForm::ERROR_MEMBER_NOT_EXISTS);

            return null;
        }

        // check if the email is the same
        if ($member->getEmail() != $data['email']) {
            $form->setError(RegisterForm::ERROR_WRONG_EMAIL);

            return null;
        }

        // check if the member already has a corresponding user.
        $user = $this->userMapper->findByLidnr($member->getLidnr());
        if (null !== $user) {
            $form->setError(RegisterForm::ERROR_USER_ALREADY_EXISTS);

            return null;
        }

        $newUser = $this->newUserMapper->getByLidnr($data['lidnr']);
        if (null !== $newUser) {
            $time = $newUser->getTime();
            $requiredInterval = (new DateTime())->sub(new DateInterval('PT900S'));
            if ($time > $requiredInterval) {
                $form->setError(RegisterForm::ERROR_ALREADY_REGISTERED);

                return null;
            }
            $this->newUserMapper->deleteByMember($member);
        }

        // save the data, and send email
        $newUser = new NewUserModel($member);
        $newUser->setCode($this->generateCode());
        $newUser->setTime(new DateTime());

        $this->newUserMapper->persist($newUser);

        $this->emailService->sendRegisterEmail($newUser, $member);

        return $newUser;
    }

    /**
     * Request a password reset.
     *
     * Will also send an email to the user.
     *
     * @param array $data Reset data
     *
     * @return UserModel User. Null when the password could not be reset.
     */
    public function reset($data)
    {
        $form = $this->registerForm;
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // get the member
        $data = $form->getData();
        $member = $this->memberMapper->findByLidnr($data['lidnr']);

        // check if the member has a corresponding user.
        $user = $this->userMapper->findByLidnr($member->getLidnr());
        if (null === $user) {
            $form->setError(RegisterForm::ERROR_MEMBER_NOT_EXISTS);

            return null;
        }

        // Check if the e-mail entered and the e-mail in the database match
        if ($member->getEmail() != $data['email']) {
            $form->setError(RegisterForm::ERROR_WRONG_EMAIL);

            return null;
        }

        // Invalidate all previous password reset codes
        // Makes sure that no double password reset codes are present in the database
        $this->newUserMapper->deleteByMember($member);

        // create new activation
        $newUser = new NewUserModel($member);
        $newUser->setCode($this->generateCode());

        $this->newUserMapper->persist($newUser);

        $this->emailService->sendPasswordLostMail($newUser, $member);

        return $user;
    }

    /**
     * Change the password of a user.
     *
     * @param array $data Passworc change date
     *
     * @return bool
     */
    public function changePassword($data)
    {
        $form = $this->getPasswordForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        // check the password
        $adapter = $this->authService->getAdapter();

        if (get_class($adapter) != Mapper::class) {
            throw new RuntimeException("Adapter was not of the expected type");
        }

        $user = $this->authService->getIdentity();

        if (!$adapter->verifyPassword($data['old_password'], $user->getPassword())) {
            $form->setMessages([
                'old_password' => [
                    $this->translator->translate('Password incorrect'),
                ],
            ]);

            return false;
        }

        $mapper = $this->userMapper;

        // get the actual user and save
        $actUser = $mapper->findByLidnr($user->getLidnr());

        $actUser->setPassword($this->bcrypt->create($data['password']));

        $mapper->persist($actUser);

        return true;
    }

    /**
     * Log the user in.
     *
     * @param array $data Login data
     *
     * @return UserModel Authenticated user. Null if not authenticated.
     */
    public function login($data)
    {
        $form = $this->getLoginForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // Try to authenticate the user.
        $result = $this->authService->authenticate($data['login'], $data['password']);

        // Check if authentication was successful.
        if (!$result->isValid()) {
            $form->setResult($result);

            return null;
        }

        $this->authService->setRememberMe($data['remember']);

        return $this->authService->getIdentity();
    }

    /**
     * Login using a pin code.
     *
     * @param array $data
     *
     * @return UserModel Authenticated user. Null if not authenticated.
     */
    public function pinLogin($data)
    {
        if (!$this->aclService->isAllowed('pin_login', 'user')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to login using pin codes')
            );
        }

        // Try to authenticate the user.
        $result = $this->pinAuthService->authenticate($data['lidnr'], $data['pincode']);

        // Check if authentication was successful.
        if (!$result->isValid()) {
            return null;
        }

        return $this->authService->getIdentity();
    }

    /**
     * Log the user out.
     */
    public function logout()
    {
        // clear the user identity
        $this->authService->clearIdentity();
    }

    /**
     * Get the new user.
     *
     * @param string $code
     *
     * @return NewUserModel
     */
    public function getNewUser($code)
    {
        return $this->newUserMapper->getByCode($code);
    }

    /**
     * Generate an activation code for the user.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateCode($length = 20)
    {
        $ret = '';
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($i = 0; $i < $length; ++$i) {
            $ret .= $alphabet[rand(0, strlen($alphabet) - 1)];
        }

        return $ret;
    }

    /**
     * Get the activate form.
     *
     * @return Activate Activate form
     */
    public function getActivateForm()
    {
        return $this->activateForm;
    }

    /**
     * Get the register form.
     *
     * @return RegisterForm Register form
     */
    public function getRegisterForm()
    {
        return $this->registerForm;
    }

    /**
     * Get the password form.
     *
     * @return Password Password change form
     */
    public function getPasswordForm()
    {
        if (!$this->aclService->isAllowed('password_change', 'user')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to change your password')
            );
        }

        return $this->passwordForm;
    }

    /**
     * Get the login form.
     *
     * @return Login Login form
     */
    public function getLoginForm()
    {
        return $this->loginForm;
    }
}
