<?php

namespace User\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface,
    Zend\Authentication\Result,
    User\Mapper\User as UserMapper,
    User\Model\User as UserModel;
use Zend\Crypt\Password\Bcrypt;

class Mapper implements AdapterInterface
{

    /**
     * Mapper.
     *
     * @var UserMapper
     */
    protected $mapper;

    /**
     * Email.
     *
     * @var string
     */
    protected $email;

    /**
     * Password.
     *
     * @var string
     */
    protected $password;

    /**
     * Bcrypt instance.
     *
     * @var Bcrypt
     */
    protected $bcrypt;


    /**
     * Constructor.
     *
     * @param Bcrypt $bcrypt
     */
    public function __construct(Bcrypt $bcrypt)
    {
        $this->bcrypt = $bcrypt;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate()
    {
        $mapper = $this->getMapper();

        $user = $mapper->findByLogin($this->login);

        if (null === $user) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
                []
            );
        }

        if (!$this->verifyPassword($user)) {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
                []
            );
        }

        return new Result(Result::SUCCESS, $user);
    }

    /**
     * Verify the password.
     *
     * @param UserModel $user
     *
     * @return boolean
     */
    protected function verifyPassword(UserModel $user)
    {
        return $this->bcrypt->verify($this->password, $user->getPassword());
    }

    /**
     * Set the credentials.
     *
     * @param array $data
     */
    public function setCredentials($data)
    {
        $this->login = $data['login'];
        $this->password = $data['password'];
    }

    /**
     * Get the mapper.
     *
     * @return UserMapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Set the mapper.
     *
     * @param UserMapper $mapper
     */
    public function setMapper(UserMapper $mapper)
    {
        $this->mapper = $mapper;
    }
}
