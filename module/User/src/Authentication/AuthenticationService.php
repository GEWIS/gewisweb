<?php

namespace User\Authentication;

use Application\Model\IdentityInterface;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Storage\StorageInterface;
use RuntimeException;
use Laminas\Authentication\{
    AuthenticationServiceInterface,
    Result,
};
use User\Authentication\Adapter\{
    CompanyUserAdapter,
    UserAdapter,
};
use User\Authentication\Storage\{
    CompanyUserSession,
    UserSession,
};
use User\Model\{
    CompanyUser,
    User,
};

/**
 * @template TStorage of CompanyUserSession|UserSession
 * @template TAdapter of CompanyUserAdapter|UserAdapter
 */
class AuthenticationService implements AuthenticationServiceInterface
{
    /** @psalm-var TStorage $storage */
    private CompanyUserSession|UserSession $storage;

    /** @psalm-var TAdapter $storage */
    private CompanyUserAdapter|UserAdapter $adapter;

    /**
     * @psalm-param TStorage $storage
     * @psalm-param TAdapter $adapter
     */
    public function __construct(
        CompanyUserSession|UserSession $storage,
        CompanyUserAdapter|UserAdapter $adapter,
    ) {
        $this->setStorage($storage);
        $this->setAdapter($adapter);
    }

    /**
     * Returns the authentication adapter.
     *
     * @psalm-return TAdapter
     */
    public function getAdapter(): CompanyUserAdapter|UserAdapter
    {
        return $this->adapter;
    }

    /**
     * Sets the authentication adapter.
     *
     * @psalm-param TAdapter $adapter
     */
    public function setAdapter(CompanyUserAdapter|UserAdapter $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Returns the persistent storage handler.
     *
     * @psalm-return TStorage
     */
    public function getStorage(): CompanyUserSession|UserSession
    {
        return $this->storage;
    }

    /**
     * Sets the persistent storage handler.
     *
     * @psalm-param TStorage $storage
     */
    public function setStorage(CompanyUserSession|UserSession $storage): self
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * Authenticates against the authentication adapter. The default values must be `null` to be compatible with the
     * `AuthenticationServiceInterface`. We can safely assume they are provided, but if not throw a `RuntimeException`.
     */
    public function authenticate(
        ?string $login = null,
        ?string $securityCode = null,
    ): Result {
        if (
            null === $login
            || null === $securityCode
        ) {
            throw new RuntimeException('Cannot authenticate against nothing.');
        }

        // Load the credentials into the authentication adapter and authenticate.
        $this->getAdapter()->setCredentials($login, $securityCode);
        $result = $this->getAdapter()->authenticate();

        // Remove any existing identity to ensure we are starting from a blank slate.
        if ($this->hasIdentity()) {
            $this->clearIdentity();
        }

        // If the authentication was successful, persist the identity.
        if ($result->isValid()) {
            $this->getStorage()->write($result->getIdentity()->getId());
        }

        return $result;
    }

    /**
     * Returns true if and only if an identity is available from storage.
     */
    public function hasIdentity(): bool
    {
        return !$this->getStorage()->isEmpty();
    }

    /**
     * Returns the authenticated CompanyUser|User or null if no identity is available.
     *
     * @psalm-return (TAdapter is CompanyUserAdapter ? CompanyUser|null : User|null)
     */
    public function getIdentity(): ?IdentityInterface
    {
        if (!$this->hasIdentity()) {
            return null;
        }

        $mapper = $this->getAdapter()->getMapper();
        $id = $this->getStorage()->read();

        return $mapper->find($id);
    }

    /**
     * Clears the identity from persistent storage.
     */
    public function clearIdentity(): void
    {
        $this->getStorage()->clear();
    }

    /**
     * Set whether we should remember this session or not.
     */
    public function setRememberMe(bool $rememberMe): void
    {
        if ($this->getStorage() instanceof CompanyUserSession) {
            throw new RuntimeException('CompanyUserSession storage does not allow for persistent sessions.');
        }

        $this->getStorage()->setRememberMe($rememberMe);
    }
}
