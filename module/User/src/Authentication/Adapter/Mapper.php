<?php

namespace User\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use Laminas\Crypt\Password\Bcrypt;
use RuntimeException;
use User\Mapper\User as UserMapper;
use User\Model\LoginAttempt as LoginAttemptModel;
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
     * @var string
     */
    private string $login;

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
     * Constructor.
     * @param Bcrypt $bcrypt
     * @param LoginAttemptService $loginAttemptService
     * @param UserMapper $mapper
     */
    public function __construct(
        Bcrypt $bcrypt,
        LoginAttemptService $loginAttemptService,
        UserMapper $mapper,
    ) {
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

        $this->mapper->detach($user);

        if ($this->loginAttemptService->loginAttemptsExceeded($user)) {
            return new Result(
                Result::FAILURE,
                null,
            );
        }

        if (!$this->verifyPassword($this->password, $user->getPassword())) {
            $this->loginAttemptService->logFailedLogin($user);

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
    public function verifyPassword(
        string $password,
        string $hash,
    ): bool {
        if ($this->bcrypt->verify($password, $hash)) {
            return true;
        }

        return false;
    }

    /**
     * Sets the credentials used to authenticate.
     *
     * @param string $login
     * @param string $password
     */
    public function setCredentials(
        string $login,
        string $password,
    ): void {
        $this->login = $login;
        $this->password = $password;
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
