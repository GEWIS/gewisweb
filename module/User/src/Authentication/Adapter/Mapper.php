<?php

namespace User\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use Laminas\Crypt\Password\Bcrypt;
use RuntimeException;
use User\Mapper\User as UserMapper;
use User\Model\LoginAttempt;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;

class Mapper implements AdapterInterface
{
    /**
     * Mapper.
     *
     * @var UserMapper
     */
    protected UserMapper $mapper;

    /**
     * Password.
     *
     * @var string
     */
    protected string $password;

    /**
     * Bcrypt instance.
     *
     * @var Bcrypt
     */
    protected Bcrypt $bcrypt;

    /**
     * User Service
     * (for logging failed login attempts).
     *
     * @var LoginAttemptService
     */
    protected LoginAttemptService $loginAttemptService;

    /**
     * @var mixed
     */
    private $login;

    /**
     * Constructor.
     * @param Bcrypt $bcrypt
     * @param LoginAttemptService $loginAttemptService
     * @param UserMapper $mapper
     */
    public function __construct(Bcrypt $bcrypt, loginAttemptService $loginAttemptService, UserMapper $mapper)
    {
        $this->bcrypt = $bcrypt;
        $this->loginAttemptService = $loginAttemptService;
        $this->mapper = $mapper;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate(): Result
    {
        $user = $this->mapper->findByLogin($this->login);

        if (null === $user) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
            );
        }

        if ($this->loginAttemptService->loginAttemptsExceeded(LoginAttempt::TYPE_NORMAL, $user)) {
            return new Result(
                Result::FAILURE,
                null,
            );
        }

        if (!$this->verifyPassword($this->password, $user->getPassword())) {
            $this->loginAttemptService->logFailedLogin($user, LoginAttempt::TYPE_NORMAL);

            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
            );
        }

        return new Result(Result::SUCCESS, $user);
    }

    /**
     * Verify a password.
     *
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function verifyPassword($password, $hash)
    {
        if (0 === strlen($hash)) {
            throw new RuntimeException("Legacy service is not available for Mapper Auth.");
        }

        if ($this->bcrypt->verify($password, $hash)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $login
     * @param string $password
     * @return Result
     */
    public function authenticateWithCredentials($login, $password): Result
    {
        $this->login = $login;
        $this->password = $password;
        return $this->authenticate();
    }

    /**
     * Get the mapper.
     *
     * @return UserMapper
     */
    public function getMapper(): UserMapper
    {
        return $this->mapper;
    }
}
