<?php

namespace User\Service;

use Application\Service\AbstractAclService;
use DateInterval;
use DateTime;
use User\Form\Register as RegisterForm;
use User\Mapper\User as UserMapper;
use User\Model\CompanyUser as CompanyUserModel;
use User\Model\LoginAttempt as LoginAttemptModel;
use User\Model\NewUser as NewUserModel;
use User\Model\NewCompany as NewCompanyModel;
use User\Model\User as UserModel;
use User\Permissions\NotAllowedException;

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

    // TODO: comments
    public function activateCompany($data, NewCompanyModel $newCompany)
    {
        $form = $this->getActivateForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $bcrypt = $this->sm->get('user_bcrypt');

        // first try to obtain the user
        $companyUser = $this->getCompanyMapper()->findById($newCompany->getId());
        if (null === $companyUser) {
            // create a new user from this data, and insert it into the database
            $companyUser = new CompanyUserModel($newCompany);
        }

        $companyUser->setPassword($bcrypt->create($data['password']));

        // this will also save a user with a lost password
        $this->getCompanyMapper()->createCompany($companyUser, $newCompany);

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

        $newUser = $this->getNewUserMapper()->getByLidnr($data['lidnr']);
        if (null !== $newUser) {
            $time = $newUser->getTime();
            $requiredInterval = (new DateTime())->sub(new DateInterval('PT900S'));
            if ($time > $requiredInterval) {
                $form->setError(RegisterForm::ERROR_ALREADY_REGISTERED);

                return null;
            }
            $this->getNewUserMapper()->deleteByMember($member);
        }

        // save the data, and send email
        $newUser = new NewUserModel($member);
        $newUser->setCode($this->generateCode());
        $newUser->setTime(new DateTime());

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

    public function resetCompany($data)
    {
        $form = $this->getCompanyPasswordResetForm();
        $form->setData($data);

        if (!$form->isValid()) {
            echo "Test";
            return null;
        }

        // get the member
        $data = $form->getData();
        $company = $this->getCompanyMapper()->findByEmail($data['email']);

        #check if the member has a corresponding user.
//        $user = $this->getCompanyMapper()->findById($member->getLidnr());
//        echo $user;
        if (null === $company) {
            $form->setError(RegisterForm::ERROR_MEMBER_NOT_EXISTS);
            echo "Company is null";
            return null;
        }

        #Check if the e-mail entered and the e-mail in the database match
        if ($company->getContactEmail() != $data['email']) {
            $form->setError(RegisterForm::ERROR_WRONG_EMAIL);
            return null;
        }

        // Invalidate all previous password reset codes
        // Makes sure that no double password reset codes are present in the database
        $newCompany = $this->getNewCompanyMapper()->findByEmail($data['email']);
        $this->getNewCompanyMapper()->deleteByCompany($data['email']);


        // create new activation
        $newUser = new NewCompanyModel($company);
        $newUser->setCode($newUser->generateCode());

        $this->getNewCompanyMapper()->persist($newUser);

        $this->getEmailService()->sendCompanyPasswordLostMail($newUser, $company);

        return $company;
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
     * Log the company in.
     *
     * @param array $data Login data
     *
     * @return CompanyModel Authenticated company. Null if not authenticated.
     */
    public function companyLogin($data)
    {
        $form = $this->getCompanyLoginForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // try to authenticate
        $auth = $this->getServiceManager()->get('company_auth_service');
        $authAdapter = $auth->getAdapter();

        $authAdapter->setCredentials($form->getData());

        $result = $auth->authenticate();

        // process the result
        if (!$result->isValid()) {
            $form->setResult($result);
            return null;
        }

        $this->getAuthStorage()->setRememberMe($data['remember']);
        $company = $auth->getIdentity();

        return $company;
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

    public function getCompanyIdentity() {
        $authService = $this->getServiceManager()->get('company_auth_service');
        if (!$authService->hasCompanyIdentity()) {
            $translator = $this->getServiceManager()->get('translator');
            throw new NotAllowedException(
                $translator->translate('You need to log in to perform this action')
            );
        }
        return $authService->getIdentity();
    }

    public function hasCompanyIdentity()
    {
        $authService = $this->getServiceManager()->get('company_auth_service');
        return $authService->hasCompanyIdentity();
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

    /**
     * Checks whether the user is logged in
     *
     * @return Bool true if the user is logged in, false otherwise
     */
    public function hasIdentity()
    {
        $authService = $this->getServiceManager()->get('user_auth_service');
        return $authService->hasIdentity();
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

    // TODO: comments
    public function getNewCompany($code)
    {
        return $this->getNewCompanyMapper()->getByCode($code);
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
     * Get the company password reset form.
     */
    public function getCompanyPasswordResetForm()
    {
        return $this->sm->get('user_form_companypasswordreset');
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
     * Get the company login form.
     *
     * @return Company Login form
     */
    public function getCompanyLoginForm()
    {
        return $this->sm->get('user_form_companylogin');
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

    // TODO: comments
    /**
     * Get the new company mapper.
     *
     * @return NewCompanyMapper
     */
    public function getNewCompanyMapper()
    {
        return $this->sm->get('user_mapper_newcompany');
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

    // TODO: comments
    public function getCompanyMapper()
    {
        return $this->sm->get('user_mapper_company');
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
