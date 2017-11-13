<?php

namespace User\Service;

use Application\Service\AbstractAclService;

use User\Model\User as UserModel;
use User\Model\NewUser as NewUserModel;
use User\Model\LoginAttempt as LoginAttemptModel;
use User\Mapper\User as UserMapper;
use User\Model\Session as SessionModel;
use User\Permissions\NotAllowedException;
use User\Form\Register as RegisterForm;

/**
 * User service.
 */
class User extends AbstractAclService
{

    /**
     * Activate a user.
     *
     * @param array $data Activation data.
     * @param NewUserModel $newUser The user to create
     *
     * @return boolean
     */
    public function activate($data, NewUserModel $newUser)
    {
        $form = $this->getActivateForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $bcrypt = $this->sm->get('user_bcrypt');

        // first try to obtain the user
        $user = $this->getUserMapper()->findByLidnr($newUser->getLidnr());
        if (null === $user) {
            // create a new user from this data, and insert it into the database
            $user = new UserModel($newUser);
        }

        $user->setPassword($bcrypt->create($data['password']));

        // this will also save a user with a lost password
        $this->getUserMapper()->createUser($user, $newUser);

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
        $form = $this->getRegisterForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // get the member
        $data = $form->getData();
        $member = $this->getMemberMapper()->findByLidnr($data['lidnr']);

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
        $user = $this->getUserMapper()->findByLidnr($member->getLidnr());
        if (null !== $user) {
            $form->setError(RegisterForm::ERROR_USER_ALREADY_EXISTS);

            return null;
        }

        // save the data, and send email
        $newUser = new NewUserModel($member);
        $newUser->setCode($this->generateCode());

        $this->getNewUserMapper()->persist($newUser);

        $this->getEmailService()->sendRegisterEmail($newUser, $member);

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
        $form = $this->getPasswordResetForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // get the member
        $data = $form->getData();
        $member = $this->getMemberMapper()->findByLidnr($data['lidnr']);

        // check if the member has a corresponding user.
        $user = $this->getUserMapper()->findByLidnr($member->getLidnr());
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
        $this->getNewUserMapper()->deleteByMember($member);

        // create new activation
        $newUser = new NewUserModel($member);
        $newUser->setCode($this->generateCode());

        $this->getNewUserMapper()->persist($newUser);

        $this->getEmailService()->sendPasswordLostMail($newUser, $member);

        return $user;
    }

    /**
     * Change the password of a user.
     *
     * @param array $data Passworc change date
     *
     * @return boolean
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
        $auth = $this->getServiceManager()->get('user_auth_service');
        $adapter = $auth->getAdapter();

        $user = $auth->getIdentity();

        if (!$adapter->verifyPassword($data['old_password'], $user->getPassword())) {
            $form->setMessages([
                'old_password' => [
                    $this->getTranslator()->translate("Password incorrect")
                ]
            ]);

            return false;
        }

        $mapper = $this->getUserMapper();
        $bcrypt = $this->sm->get('user_bcrypt');

        // get the actual user and save
        $actUser = $mapper->findByLidnr($user->getLidnr());

        $actUser->setPassword($bcrypt->create($data['password']));

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

        // try to authenticate
        $auth = $this->getServiceManager()->get('user_auth_service');
        $authAdapter = $auth->getAdapter();

        $authAdapter->setCredentials($form->getData());

        $result = $auth->authenticate();

        // process the result
        if (!$result->isValid()) {
            $form->setResult($result);

            return null;
        }

        $this->getAuthStorage()->setRememberMe($data['remember']);
        $user = $auth->getIdentity();

        return $user;
    }

    /**
     * Login using a pin code.
     *
     * @param array $data
     * @return UserModel Authenticated user. Null if not authenticated.
     */
    public function pinLogin($data)
    {
        if (!$this->isAllowed('pin_login')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to login using pin codes')
            );
        }
        // try to authenticate
        $auth = $this->getServiceManager()->get('user_pin_auth_service');
        $authAdapter = $auth->getAdapter();

        $authAdapter->setCredentials($data['lidnr'], $data['pincode']);

        $result = $auth->authenticate();

        // process the result
        if (!$result->isValid()) {
            return null;
        }

        return $auth->getIdentity();
    }

    /**
     * Log the user out.
     */
    public function logout()
    {
        // clear the user identity
        $auth = $this->getServiceManager()->get('user_auth_service');
        $auth->clearIdentity();
    }

    /**
     * Gets the user identity, or gives a 403 if the user is not logged in
     *
     * @return User the current logged in user
     * @throws NotAllowedException if no user is logged in
     */
    public function getIdentity()
    {
        $authService = $this->getServiceManager()->get('user_auth_service');
        if (!$authService->hasIdentity()) {
            $translator = $this->getServiceManager()->get('translator');
            throw new NotAllowedException(
                $translator->translate('You need to log in to perform this action')
            );
        }
        return $authService->getIdentity();
    }

    public function detachUser($user)
    {
        /*
         * Yes, this is some sort of horrible hack to make the entity manager happy again. If anyone wants to waste
         * their day figuring out what kind of dark magic is upsetting the entity manager here, be my guest.
         * This hack only is needed when we want to flush the entity manager during login.
         */
        $this->sm->get('user_doctrine_em')->clear();

        return $this->getUserMapper()->findByLidnr($user->getLidnr());
    }

    public function logFailedLogin($user, $type)
    {
        $attempt = new LoginAttemptModel();
        $attempt->setIp($this->sm->get('user_remoteaddress'));
        $attempt->setTime(new \DateTime());
        $attempt->setType($type);
        $user = $this->detachUser($user);
        $attempt->setUser($user);
        $this->getLoginAttemptMapper()->persist($attempt);
    }

    public function loginAttemptsExceeded($type, $user)
    {
        $config = $this->getRateLimitConfig();
        $ip = $this->sm->get('user_remoteaddress');
        $since = (new \DateTime())->sub(new \DateInterval('PT' . $config[$type]['lockout_time'] . 'M'));
        $loginAttemptMapper = $this->getLoginAttemptMapper();
        if ($loginAttemptMapper->getFailedAttemptCount($since, $type, $ip) > $config[$type]['ip']) {
            return true;
        }
        if ($loginAttemptMapper->getFailedAttemptCount($since, $type, $ip, $user) > $config[$type]['user']) {
            return true;
        }

        return false;
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
        return $this->getNewUserMapper()->getByCode($code);
    }

    /**
     * Generate an activation code for the user.
     *
     * @param int $length
     *
     * @return string
     */
    public function generateCode($length = 20)
    {
        $ret = '';
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($i = 0; $i < $length; $i++) {
            $ret .= $alphabet[rand(0, strlen($alphabet) - 1)];
        }

        return $ret;
    }

    /**
     * Get the activate form.
     *
     * @return ActivateForm Activate form
     */
    public function getActivateForm()
    {
        return $this->sm->get('user_form_activate');
    }

    /**
     * Get the register form.
     *
     * @return RegisterForm Register form
     */
    public function getRegisterForm()
    {
        return $this->sm->get('user_form_register');
    }

    /**
     * Get the password form.
     *
     * @return User\Form\Password Password change form
     */
    public function getPasswordForm()
    {
        if (!$this->isAllowed('password_change')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate("You are not allowed to change your password")
            );
        }

        return $this->sm->get('user_form_password');
    }

    /**
     * Get the password reset form.
     */
    public function getPasswordResetForm()
    {
        return $this->sm->get('user_form_passwordreset');
    }

    /**
     * Get the password activate form.
     */
    public function getPasswordActivateForm()
    {
        return $this->sm->get('user_form_passwordactivate');
    }

    /**
     * Get the login form.
     *
     * @return LoginForm Login form
     */
    public function getLoginForm()
    {
        return $this->sm->get('user_form_login');
    }

    /**
     * Get the member mapper.
     *
     * @return MemberMapper
     */
    public function getMemberMapper()
    {
        return $this->sm->get('decision_mapper_member');
    }

    /**
     * Get the new user mapper.
     *
     * @return NewUserMapper
     */
    public function getNewUserMapper()
    {
        return $this->sm->get('user_mapper_newuser');
    }

    /**
     * Get the user mapper.
     *
     * @return UserMapper
     */
    public function getUserMapper()
    {
        return $this->sm->get('user_mapper_user');
    }

    /**
     * Get the session mapper.
     *
     * @return \User\Mapper\Session
     */
    public function getSessionMapper()
    {
        return $this->sm->get('user_mapper_session');
    }

    /**
     * Get the login attempt mapper.
     *
     * @return \User\Mapper\LoginAttempt
     */
    public function getLoginAttemptMapper()
    {
        return $this->sm->get('user_mapper_loginattempt');
    }

    /**
     * Get the email service.
     *
     * @return EmailService
     */
    public function getEmailService()
    {
        return $this->sm->get('user_service_email');
    }

    /**
     * Get the auth storage.
     *
     * @return User\Authentication\Storage
     */
    public function getAuthStorage()
    {
        return $this->sm->get('user_auth_storage');
    }

    /**
     * Get the rate limit config
     *
     * @return array containing the config
     */
    public function getRateLimitConfig()
    {
        $config = $this->sm->get('config');

        return $config['login_rate_limits'];
    }

    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('acl');
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
        return 'user';
    }
}
