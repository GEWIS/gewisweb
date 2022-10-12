<?php

namespace User\Authentication\Adapter;

use DateTime;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use Laminas\Crypt\Password\Bcrypt;
use RuntimeException;
use User\Mapper\User as UserMapper;
use User\Model\LoginAttempt as LoginAttemptModel;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;

class Mapper implements AdapterInterface
{
    private string $login;

    private string $password;

    public function __construct(
        private readonly Bcrypt $bcrypt,
        private readonly LoginAttemptService $loginAttemptService,
        private readonly UserMapper $mapper,
    ) {
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

        if (
            $user->getMember()->getDeleted()
            || $user->getMember()->getHidden()
            || $user->getMember()->isExpired()
        ) {
            return new Result(
                Result::FAILURE_UNCATEGORIZED,
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
