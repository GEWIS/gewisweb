<?php

namespace User\Service;

use Application\Service\AbstractAclService;
use User\Form\Register as RegisterForm;
use User\Model\CompanyUser;
use User\Model\LoginAttempt as LoginAttemptModel;
use User\Model\NewUser as NewUserModel;
use User\Permissions\NotAllowedException;

/**
 * Company service.
 */
class Company extends AbstractAclService
{

    /**
     * Log the company in.
     *
     * @param array $data Login data
     *
     * @return CompanyUser Authenticated company. Null if not authenticated.
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
     * Log the user out.
     */
    public function logout()
    {
        // clear the user identity
        $auth = $this->getServiceManager()->get('company_auth_service');
        $auth->clearIdentity();
    }

    /**
     * Returns the identity of a company
     *
     * @return mixed
     */
    public function getIdentity() {
        $authService = $this->getServiceManager()->get('company_auth_service');
        if (!$authService->hasIdentity()) {
            $translator = $this->getServiceManager()->get('translator');
            throw new NotAllowedException(
                $translator->translate('You need to log in to perform this action')
            );
        }
        return $authService->getIdentity();
    }

    public function hasIdentity()
    {
        $authService = $this->getServiceManager()->get('company_auth_service');
        return $authService->hasIdentity();
    }


    /**
     * Flushes the entity manager during login.
     *
     * @param $company
     * @return CompanyUser
     */
    public function detachUser($company)
    {
        $this->sm->get('user_doctrine_em')->clear();

        return $this->getCompanyMapper()->findById($company->getLidnr());
    }

    /**
     * Persists a failed login attempt.
     *
     * @param $user
     * @param $type
     */
    public function logFailedLogin($user, $type)
    {
        $attempt = new LoginAttemptModel();
        $attempt->setIp($this->sm->get('user_remoteaddress'));
        $attempt->setTime(new \DateTime());
        $attempt->setType($type);
        $user = $this->detachUser($user);
        $attempt->setCompany($user);
        $this->getLoginAttemptMapper()->persist($attempt);
    }

    /**
     * Checks if the number of login attempts exceed the maximum amount.
     *
     * @param $type
     * @param $user
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loginAttemptsExceeded($type, $user)
    {
        $config = $this->getRateLimitConfig();
        $ip = $this->sm->get('user_remoteaddress');
        $since = (new \DateTime())->sub(new \DateInterval('PT' . $config[$type]['lockout_time'] . 'M'));
        $loginAttemptMapper = $this->getLoginAttemptMapper();
        if ($loginAttemptMapper->getCompanyFailedAttemptCount($since, $type, $ip) > $config[$type]['ip']) {
            return true;
        }
        if ($loginAttemptMapper->getCompanyFailedAttemptCount($since, $type, $ip, $user) > $config[$type]['user']) {
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

    /**
     * Get the user mapper.
     *
     * @return \User\Mapper\Company
     */
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
        return $this->sm->get('company_auth_storage');
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
        return 'company';
    }
}
