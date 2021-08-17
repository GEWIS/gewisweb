<?php

namespace User\Service;

use DateInterval;
use DateTime;
use Decision\Mapper\Member as MemberMapper;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Mvc\I18n\Translator;
use Laminas\Stdlib\Parameters;
use RuntimeException;
use User\Authentication\Adapter\Mapper;
use User\Authentication\AuthenticationService;
use User\Form\{
    Activate as ActivateForm,
    Login as LoginForm,
    Password as PasswordForm,
    Register as RegisterForm,
};
use User\Mapper\{
    NewUser as NewUserMapper,
    User as UserMapper,
};
use User\Model\{
    NewUser as NewUserModel,
    User as UserModel,
};
use User\Permissions\NotAllowedException;

/**
 * User service.
 */
class User
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var Bcrypt
     */
    private Bcrypt $bcrypt;

    /**
     * @var AuthenticationService
     * with regular Mapper adapter
     */
    private AuthenticationService $authService;

    /**
     * @var AuthenticationService
     * with PinMapper adapter
     */
    private AuthenticationService $pinAuthService;

    /**
     * @var Email
     */
    private Email $emailService;

    /**
     * @var UserMapper
     */
    private UserMapper $userMapper;

    /**
     * @var NewUserMapper
     */
    private NewUserMapper $newUserMapper;

    /**
     * @var MemberMapper
     */
    private MemberMapper $memberMapper;

    /**
     * @var RegisterForm
     */
    private RegisterForm $registerForm;

    /**
     * @var ActivateForm
     */
    private ActivateForm $activateForm;

    /**
     * @var LoginForm
     */
    private LoginForm $loginForm;

    /**
     * @var PasswordForm
     */
    private PasswordForm $passwordForm;

    /**
     * @var AclService
     */
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        Bcrypt $bcrypt,
        AuthenticationService $authService,
        AuthenticationService $pinAuthService,
        Email $emailService,
        UserMapper $userMapper,
        NewUserMapper $newUserMapper,
        MemberMapper $memberMapper,
        RegisterForm $registerForm,
        ActivateForm $activateForm,
        LoginForm $loginForm,
        PasswordForm $passwordForm,
        AclService $aclService,
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
     * @param Parameters $data activation data
     * @param NewUserModel $newUser The user to create
     *
     * @return bool
     */
    public function activate(Parameters $data, NewUserModel $newUser): bool
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
     * @param Parameters $data Registration data
     *
     * @return NewUserModel|null New registered user. Null when the user could not be registered.
     */
    public function register(Parameters $data): ?NewUserModel
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
     * @param Parameters $data Reset data
     *
     * @return UserModel|null User. Null when the password could not be reset.
     */
    public function reset(Parameters $data): ?UserModel
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
     * @param Parameters $data Passworc change date
     *
     * @return bool
     */
    public function changePassword(Parameters $data): bool
    {
        $form = $this->getPasswordForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        // check the password
        $adapter = $this->authService->getAdapter();

        if (!($adapter instanceof Mapper)) {
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
     * @param Parameters $data Login data
     *
     * @return UserModel|null Authenticated user. Null if not authenticated.
     */
    public function login(Parameters $data): ?UserModel
    {
        $form = $this->getLoginForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // Try to authenticate the user.
        $this->authService->setRememberMe($data['remember'] === 1);
        $result = $this->authService->authenticate($data['login'], $data['password']);

        // Check if authentication was successful.
        if (!$result->isValid()) {
            $form->setResult($result);

            return null;
        }

        return $this->authService->getIdentity();
    }

    /**
     * Login using a pin code.
     *
     * @param Parameters $data
     *
     * @return UserModel|null Authenticated user. Null if not authenticated.
     */
    public function pinLogin(Parameters $data): ?UserModel
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
    public function logout(): void
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
    public function getNewUser(string $code): NewUserModel
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
    public static function generateCode(int $length = 20): string
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
     * @return ActivateForm Activate form
     */
    public function getActivateForm(): ActivateForm
    {
        return $this->activateForm;
    }

    /**
     * Get the register form.
     *
     * @return RegisterForm Register form
     */
    public function getRegisterForm(): RegisterForm
    {
        return $this->registerForm;
    }

    /**
     * Get the password form.
     *
     * @return PasswordForm Password change form
     */
    public function getPasswordForm(): PasswordForm
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
     * @return LoginForm Login form
     */
    public function getLoginForm(): LoginForm
    {
        return $this->loginForm;
    }
}
