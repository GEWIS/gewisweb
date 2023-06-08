<?php

declare(strict_types=1);

namespace User\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use User\Mapper\ApiUser as ApiUserMapper;

class ApiUserAdapter implements AdapterInterface
{
    private string $token;

    public function __construct(private readonly ApiUserMapper $mapper)
    {
    }

    /**
     * Try to authenticate.
     */
    public function authenticate(): Result
    {
        $user = $this->mapper->findByToken($this->token);

        if (null === $user) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
            );
        }

        return new Result(Result::SUCCESS, $user);
    }

    /**
     * Sets the credentials used to authenticate.
     */
    public function setCredentials(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Get the mapper.
     */
    public function getMapper(): ApiUserMapper
    {
        return $this->mapper;
    }
}
