<?php

namespace User\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use User\Mapper\ApiUser as ApiUserMapper;

class ApiMapper implements AdapterInterface
{
    /**
     * Mapper.
     *
     * @var ApiUserMapper
     */
    protected ApiUserMapper $mapper;

    private string $token;

    /**
     * Constructor.
     * @param ApiUserMapper $mapper
     */
    public function __construct(ApiUserMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
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
     *
     * @param string $token
     * @return void
     */
    public function setCredentials(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the mapper.
     *
     * @return ApiUserMapper
     */
    public function getMapper(): ApiUserMapper
    {
        return $this->mapper;
    }
}
