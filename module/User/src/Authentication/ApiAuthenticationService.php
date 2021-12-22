<?php

namespace User\Authentication;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\{
    AuthenticationServiceInterface,
    Result,
};
use RuntimeException;
use User\Authentication\Adapter\ApiMapper;
use User\Model\ApiUser;

class ApiAuthenticationService implements AuthenticationServiceInterface
{
    /**
     * Authentication adapter.
     *
     * @var ApiMapper
     */
    protected ApiMapper $adapter;

    /**
     * The identity is only persisted for one request.
     *
     * @var ApiUser|null
     */
    private ?ApiUser $identity = null;

    /**
     * Constructor.
     *
     * @param ApiMapper $adapter
     *
     * @throws RuntimeException
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->setAdapter($adapter);
    }

    /**
     * Returns the authentication adapter.
     *
     * @return ApiMapper
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Sets the authentication adapter.
     *
     * @param AdapterInterface $adapter
     *
     * @return self Provides a fluent interface
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        if ($adapter instanceof ApiMapper) {
            $this->adapter = $adapter;
            return $this;
        }

        throw new RuntimeException(
            'ApiAuthenticationService expects the authentication adapter to be of type Mapper or PinMapper.'
        );
    }

    /**
     * @param string|null $token
     * @return Result
     */
    public function authenticate(string $token = null)
    {
        $this->getAdapter()->setCredentials($token);
        $result = $this->getAdapter()->authenticate();
        if ($result->isValid()) {
            $this->identity = $result->getIdentity();
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function hasIdentity()
    {
        return !is_null($this->identity);
    }

    /**
     * @return ApiUser|null
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    public function clearIdentity()
    {
        $this->identity = null;
    }
}
