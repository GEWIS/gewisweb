<?php

namespace User\Service;

use User\Model\User as UserModel,
    User\Mapper\User as UserMapper;

use Zend\ServiceManager\ServiceManager,
    Zend\ServiceManager\ServiceManagerAwareInterface;

/**
 * User service.
 */
class User implements ServiceManagerAwareInterface
{

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

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

        return $auth->getIdentity();
    }

    /**
     * Log the user out.
     *
     * @param array $data Logout data
     *
     * @return boolean If the user was logged out
     */
    public function logout($data)
    {
        $form = $this->getLogoutForm();
        $form->setData($data);

        // if the form isn't valid, the user doesn't want to logout
        if (!$form->isValid()) {
            return false;
        }

        // clear the user identity
        $auth = $this->getServiceManager()->get('user_auth_service');
        $auth->clearIdentity();

        return true;
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
     * Get the login form.
     *
     * @return LoginForm Login form
     */
    public function getLoginForm()
    {
        return $this->sm->get('user_form_login');
    }

    /**
     * Get the logout form.
     *
     * @return LogoutForm Logout form
     */
    public function getLogoutForm()
    {
        return $this->sm->get('user_form_logout');
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
     * Set the service manager.
     *
     * @param ServiceManager $sm
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    /**
     * Get the service manager.
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->sm;
    }
}
