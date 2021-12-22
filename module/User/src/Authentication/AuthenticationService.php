<?php

namespace User\Authentication;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Storage\StorageInterface;
use RuntimeException;
use Laminas\Authentication\{
    AuthenticationServiceInterface,
    Result,
};
use User\Authentication\Adapter\{
    Mapper,
    PinMapper,
};
use User\Authentication\Storage\Session;
use User\Model\User;

class AuthenticationService implements AuthenticationServiceInterface
{
    /**
     * Persistent storage handler.
     *
     * @var Session
     */
    protected Session $storage;

    /**
     * Authentication adapter.
     *
     * @var Mapper|PinMapper
     */
    protected Mapper|PinMapper $adapter;

    /**
     * Constructor.
     *
     * @param StorageInterface $storage
     * @param AdapterInterface $adapter
     *
     */
    public function __construct(StorageInterface $storage, AdapterInterface $adapter)
    {
        $this->setStorage($storage);
        $this->setAdapter($adapter);
    }

    /**
     * Returns the authentication adapter.
     *
     * @return Mapper|PinMapper
     */
    public function getAdapter(): PinMapper|Mapper
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
    public function setAdapter(AdapterInterface $adapter): self
    {
        if (
            $adapter instanceof Mapper
            || $adapter instanceof PinMapper
        ) {
            $this->adapter = $adapter;

            return $this;
        }

        throw new RuntimeException(
            'AuthenticationService expects the authentication adapter to be of type Mapper or PinMapper.'
        );
    }

    /**
     * Returns the persistent storage handler.
     *
     * @return Session
     */
    public function getStorage(): Session
    {
        return $this->storage;
    }

    /**
     * Sets the persistent storage handler.
     *
     * @param StorageInterface $storage
     *
     * @return self Provides a fluent interface
     */
    public function setStorage(StorageInterface $storage): self
    {
        if ($storage instanceof Session) {
            $this->storage = $storage;

            return $this;
        }

        throw new RuntimeException(
            'AuthenticationService expects the persistent storage handler to be of type Session.'
        );
    }

    /**
     * Authenticates against the authentication adapter. The default values must be `null` to be compatible with the
     * `AuthenticationServiceInterface`. We can safely assume they are provided, but if not throw a `RuntimeException`.
     *
     * @param mixed|null $login
     * @param string|null $securityCode
     *
     * @return Result
     *
     * @throws RuntimeException
     */
    public function authenticate(mixed $login = null, string $securityCode = null): Result
    {
        if (
            is_null($login)
            || is_null($securityCode)
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
            $this->getStorage()->write($result->getIdentity()->getLidnr());
        }

        return $result;
    }

    /**
     * Returns true if and only if an identity is available from storage.
     *
     * @return bool
     */
    public function hasIdentity(): bool
    {
        return !$this->getStorage()->isEmpty();
    }

    /**
     * Returns the authenticated User or null if no identity is available.
     *
     * @return User|null
     */
    public function getIdentity(): ?User
    {
        if (!$this->hasIdentity()) {
            return null;
        }

        $mapper = $this->getAdapter()->getMapper();
        $user = $this->getStorage()->read();

        return $mapper->findByLidnr($user);
    }

    /**
     * Clears the identity from persistent storage
     *
     * @return void
     */
    public function clearIdentity(): void
    {
        $this->getStorage()->clear();
    }

    /**
     * Set whether we should remember this session or not.
     *
     * @param bool $rememberMe
     *
     * @return void
     */
    public function setRememberMe(bool $rememberMe): void
    {
        $this->getStorage()->setRememberMe($rememberMe);
    }
}
