<?php

namespace User\Service;

use User\Model\User as UserModel;
use User\Model\NewUser as NewUserModel;
use User\Mapper\User as UserMapper;

use Decision\Model\Member as MemberModel;

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

        // create a new user from this data, and insert it into the database
        $user = new UserModel($newUser);
        $user->setPassword($bcrypt->create($data['password']));

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

        // check if the email is the same
        if ($member->getEmail() != $data['email']) {
            // TODO: error, wrong email address
            return null;
        }

        // check if the member already has a corresponding user.
        $user = $this->getUserMapper()->findByLidnr($member->getLidnr());
        if (null !== $user) {
            // TODO: error, user already exists
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
     * Get the email service.
     *
     * @return EmailService
     */
    public function getEmailService()
    {
        return $this->sm->get('user_service_email');
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
