<?php

namespace User\Authentication\Adapter;

use Application\Service\AbstractAclService;
use User\Model\LoginAttempt;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;
use User\Mapper\Mapper as AbstractMapper;
use User\Model\Model as AbstractModel;
use Zend\Crypt\Password\Bcrypt;
use Application\Service\Legacy as LegacyService;
use User\Service\User as UserService;

class Mapper implements AdapterInterface
{
    /**
     * Mapper.
     *
     * @var AbstractMapper
     */
    protected $mapper;

    /**
     * Email.
     *
     * @var string
     */
    protected $email;

    /**
     * Password.
     *
     * @var string
     */
    protected $password;

    /**
     * Bcrypt instance.
     *
     * @var Bcrypt
     */
    protected $bcrypt;

    /**
     * Legacy Service
     * (for checking logins against the old database)
     *
     * @var LegacyService
     */
    protected $legacyService;

    /**
     * User Service
     * (for logging failed login attempts)
     *
     * @var AbstractAclService
     */
    protected $accountService;

    /**
     * Constructor.
     *
     * @param Bcrypt $bcrypt
     */
    public function __construct(Bcrypt $bcrypt, LegacyService $legacyService, AbstractAclService $accountService)
    {
        $this->bcrypt = $bcrypt;
        $this->legacyService = $legacyService;
        $this->accountService = $accountService;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate()
    {
        $user = $this->mapper->findByLogin($this->login);

        if (null === $user) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
                []
            );
        }

        $this->mapper->detach($user);

        if ($this->accountService->loginAttemptsExceeded(LoginAttempt::TYPE_NORMAL, $user)) {
            return new Result(
                Result::FAILURE,
                null,
                []
            );
        }

        if (!$this->verifyPassword($this->password, $user->getPassword(), $user)) {
            $this->accountService->logFailedLogin($user, LoginAttempt::TYPE_NORMAL);
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
                []
            );
        }

        return new Result(Result::SUCCESS, $user);
    }

    /**
     * Verify a password.
     *
     * @param string $password
     * @param string $hash
     * @param AbstractModel $user
     *
     * @return boolean
     */
    public function verifyPassword($password, $hash, $user = null)
    {
        if (strlen($hash) === 0) {
            return $this->legacyService->checkPassword($user, $password, $this->bcrypt);
        }

        if ($this->bcrypt->verify($password, $hash)) {
            return true;
        }

        return false;
    }

    /**
     * Set the credentials.
     *
     * @param array $data
     */
    public function setCredentials($data)
    {
        $this->login = $data['login'];
        $this->password = $data['password'];
    }

    /**
     * Get the mapper.
     *
     * @return AbstractMapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Set the mapper.
     *
     * @param AbstractMapper $mapper
     */
    public function setMapper(\User\Mapper\Mapper $mapper)
    {
        $this->mapper = $mapper;
    }
}
