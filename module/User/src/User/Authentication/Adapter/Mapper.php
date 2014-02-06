<?php

namespace User\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface,
    Zend\Authentication\Result,
    User\Mapper\User as UserMapper,
    User\Model\User as UserModel;

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
                array()
            );
        }

        if (!$user->verifyPassword($this->password)) {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
                array()
            );
        }

        return new Result(Result::SUCCESS, $user);
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
