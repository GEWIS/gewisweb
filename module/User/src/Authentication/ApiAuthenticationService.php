<?php

declare(strict_types=1);

namespace User\Authentication;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\{
    AuthenticationServiceInterface,
    Result,
};
use RuntimeException;
use User\Authentication\Adapter\ApiUserAdapter;
use User\Model\ApiUser;

class ApiAuthenticationService implements AuthenticationServiceInterface
{
    private ApiUserAdapter $adapter;

    /**
     * The identity is only persisted for one request.
     */
    private ?ApiUser $identity = null;

    public function __construct(ApiUserAdapter $adapter)
    {
        $this->setAdapter($adapter);
    }

    /**
     * Returns the authentication adapter.
     */
    public function getAdapter(): ApiUserAdapter
    {
        return $this->adapter;
    }

    /**
     * Sets the authentication adapter.
     */
    public function setAdapter(ApiUserAdapter $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Authenticates against the authentication adapter. The default values must be `null` to be compatible with the
     * `AuthenticationServiceInterface`.
     */
    public function authenticate(?string $token = null): Result
    {
        $this->getAdapter()->setCredentials($token);
        $result = $this->getAdapter()->authenticate();

        if ($result->isValid()) {
            $this->identity = $result->getIdentity();
        }

        return $result;
    }

    /**
     * Returns true if and only if an identity is available.
     */
    public function hasIdentity(): bool
    {
        return !is_null($this->identity);
    }

    /**
     * Returns the authenticated ApiUser or null if no identity is available.
     */
    public function getIdentity(): ?ApiUser
    {
        return $this->identity;
    }

    /**
     * Clears the identity.
     */
    public function clearIdentity(): void
    {
        $this->identity = null;
    }
}
