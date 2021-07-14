<?php

namespace User\Authentication;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\AuthenticationService as LaminasAuthService;
use Laminas\Authentication\Result;
use Laminas\Authentication\Storage\StorageInterface;
use RuntimeException;
use User\Authentication\Adapter\Mapper;
use User\Authentication\Adapter\PinMapper;
use User\Authentication\Storage\Session;
use User\Model\User;

class AuthenticationService extends LaminasAuthService
{
    /**
     * Persistent storage handler
     *
     * @var Session
     */
    protected $storage = null;

    /**
     * Authentication adapter
     *
     * @var Mapper|PinMapper|null
     */
    protected $adapter = null;

    /**
     * Constructor
     *
     * @param Session $storage
     * @param Mapper|PinMapper $adapter
     */
    public function __construct(StorageInterface $storage = null, AdapterInterface $adapter = null)
    {
        if (
            null !== $storage
            && get_class($storage) !== Session::class
        ) {
            throw new RuntimeException("Invalid storage passed to Auth service.");
        }

        if (
            null !== $adapter
            && (get_class($adapter) !== Mapper::class && get_class($adapter) !== PinMapper::class)
        ) {
            throw new RuntimeException("Invalid adapter passed to Auth service.");
        }

        parent::__construct($storage, $adapter);
    }

    /**
     * Sets the persistent storage handler
     *
     * @param Session $storage
     * @return AuthenticationService Provides a fluent interface
     */
    public function setStorage(StorageInterface $storage)
    {
        if (get_class($storage) !== Session::class) {
            throw new RuntimeException("Invalid storage passed to Auth service.");
        }

        return parent::setStorage($storage);
    }

    /**
     * Sets the authentication adapter
     *
     * @param Mapper|PinMapper $adapter
     * @return AuthenticationService Provides a fluent interface
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        if (
            get_class($adapter) !== Mapper::class
            && get_class($adapter) !== PinMapper::class
        ) {
            throw new RuntimeException("Invalid adapter passed to Auth service.");
        }

        return parent::setAdapter($adapter);
    }

    /**
     * @return User|null
     */
    public function getIdentity()
    {
        if ($this->storage->isEmpty()) {
            return null;
        }

        $mapper = $this->adapter->getMapper();
        $user = $this->storage->read();
        if (is_object($user)) {
            $user = $user->getLidnr();
        }
        return $mapper->findByLidnr($user);
    }

    /**
     * @param string $login
     * @param string $securityCode
     * @return Result
     */
    public function authenticateWithCredentials($login, $securityCode): Result
    {
        return $this->adapter->authenticateWithCredentials($login, $securityCode);
    }

    /**
     * Set whether we should remember this session or not.
     *
     * @param int $rememberMe
     */
    public function setRememberMe($rememberMe = 0)
    {
        $this->storage->setRememberMe($rememberMe);
    }
}
