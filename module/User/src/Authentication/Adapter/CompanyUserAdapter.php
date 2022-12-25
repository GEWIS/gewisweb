<?php

namespace User\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use Laminas\Crypt\Password\Bcrypt;
use User\Mapper\CompanyUser as CompanyUserMapper;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;

class CompanyUserAdapter implements AdapterInterface
{
    private string $email;

    private string $password;

    public function __construct(
        private readonly Bcrypt $bcrypt,
        private readonly LoginAttemptService $loginAttemptService,
        private readonly CompanyUserMapper $mapper,
    ) {
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate(): Result
    {
        $company = $this->mapper->findByLogin($this->email);

        if (null === $company) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
            );
        }

        $this->mapper->detach($company);

        if ($this->loginAttemptService->loginAttemptsExceeded($company)) {
            return new Result(
                Result::FAILURE,
                null,
            );
        }

        if (!$this->verifyPassword($this->password, $company->getPassword())) {
            $this->loginAttemptService->logFailedLogin($company);

            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
            );
        }

        return new Result(Result::SUCCESS, $company);
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
     * @param string $email
     * @param string $password
     */
    public function setCredentials(
        string $email,
        string $password,
    ): void {
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Get the mapper.
     *
     * @return CompanyUserMapper
     */
    public function getMapper(): CompanyUserMapper
    {
        return $this->mapper;
    }
}
