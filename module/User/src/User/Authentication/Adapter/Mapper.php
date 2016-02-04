<?php

namespace User\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface,
    Zend\Authentication\Result,
    User\Mapper\User as UserMapper,
    User\Model\User as UserModel;
use Zend\Crypt\Password\Bcrypt;
use Application\Service\Legacy as LegacyService;
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
     * Legacy Service
     * (for checking logins against the old database)
     *
     * @var LegacyService
     */
     protected $legacyService;

    /**
     * Constructor.
     *
     * @param Bcrypt $bcrypt
     */
    public function __construct(Bcrypt $bcrypt, LegacyService $legacyService)
    {
        $this->bcrypt = $bcrypt;
        $this->legacyService = $legacyService;
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
        $mapper->detach($user);

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
        if (strlen($user->getPassword()) === 0) {
            return $this->legacyService->checkPassword($user, $this->password, $this->bcrypt);
        }

        if ($this->bcrypt->verify($this->password, $user->getPassword())) {
            return true;
        }

        return false;
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
