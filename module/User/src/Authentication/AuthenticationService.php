<?php

namespace User\Authentication;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Authentication\Result;
use Laminas\Authentication\Storage\StorageInterface;
use RuntimeException;
use User\Authentication\Adapter\Mapper;
use User\Authentication\Adapter\PinMapper;
use User\Authentication\Storage\Session;
use User\Model\User;

class AuthenticationService implements AuthenticationServiceInterface
{
    /**
     * Persistent storage handler.
     *
     * @var Session
     */
    protected $storage;

    /**
     * Authentication adapter.
     *
     * @var Mapper|PinMapper
     */
    protected $adapter;

    /**
     * Constructor.
     *
     * @param Session $storage
     * @param Mapper|PinMapper $adapter
     *
     * @throws RuntimeException
     */
    public function __construct(StorageInterface $storage, AdapterInterface $adapter)
    {
        if (get_class($storage) !== Session::class) {
            throw new RuntimeException(
                'AuthenticationService expects the persistent storage handler to be of type Session.'
            );
        }

        if (
            get_class($adapter) !== Mapper::class
            && get_class($adapter) !== PinMapper::class
        ) {
            throw new RuntimeException(
                'AuthenticationService expects the authentication adapter to be of type Mapper or PinMapper.'
            );
        }

        $this->setStorage($storage);
        $this->setAdapter($adapter);
    }

    /**
     * Returns the authentication adapter.
     *
     * @return Mapper|PinMapper
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Sets the authentication adapter.
     *
     * @param  AdapterInterface $adapter
     *
     * @return self Provides a fluent interface
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Returns the persistent storage handler.
     *
     * @return Session
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Sets the persistent storage handler.
     *
     * @param  StorageInterface $storage
     *
     * @return self Provides a fluent interface
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * Authenticates against the authentication adapter. The default values must be `null` to be compatible with the
     * `AuthenticationServiceInterface`. We can safely assume they are provided, but if not throw a `RuntimeException`.
     *
     * @param mixed $login
     * @param string $securityCode
     *
     * @return Result
     *
     * @throws RuntimeException
     */
    public function authenticate($login = null, $securityCode = null)
    {
        if (
            is_null($login)
            || is_null($securityCode)
        ) {
            throw new RuntimeException('Cannot authenticate against nothing.');
        }

        // Load the credentials into the authentication adapter and authenticate.
        $adapter = $this->getAdapter();
        $adapter->setCredentials($login, $securityCode);
        $result = $adapter->authenticate();

        // Remove any existing identity to ensure we are starting from a blank slate.
        if ($this->hasIdentity()) {
            $this->clearIdentity();
        }

        // If the authentication was successful, persist the identity.
        if ($result->isValid()) {
            $this->getStorage()->write($result->getIdentity());
        }

        return $result;
    }

    /**
     * Returns true if and only if an identity is available from storage.
     *
     * @return bool
     */
    public function hasIdentity()
    {
        return !$this->getStorage()->isEmpty();
    }

    /**
     * Returns the authenticated User or null if no identity is available.
     *
     * @return User|null
     */
    public function getIdentity()
    {
        if ($this->getStorage()->isEmpty()) {
            return null;
        }

        $mapper = $this->getAdapter()->getMapper();
        $user = $this->getStorage()->read();

        if (is_object($user)) {
            $user = $user->getLidnr();
        }

        return $mapper->findByLidnr($user);
    }

    /**
     * Clears the identity from persistent storage
     *
     * @return void
     */
    public function clearIdentity()
    {
        $this->getStorage()->clear();
    }

    /**
     * Set whether we should remember this session or not.
     *
     * @param int $rememberMe
     *
     * @return void
     */
    public function setRememberMe($rememberMe = 0)
    {
        $this->getStorage()->setRememberMe($rememberMe);
    }
}
