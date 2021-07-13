<?php

namespace User\Authentication\Adapter;

use Application\Service\Legacy as LegacyService;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use User\Mapper\User as UserMapper;
use User\Model\LoginAttempt;
use User\Model\User as UserModel;
use User\Model\UserRole as UserRoleModel;
use User\Service\LoginAttempt as LoginAttemptService;

class PinMapper implements AdapterInterface
{
    /**
     * Mapper.
     *
     * @var UserMapper
     */
    protected $mapper;

    /**
     * Legacy service.
     *
     * @var LegacyService
     */
    protected $legacyService;

    /**
     * User Service
     * (for logging failed login attempts).
     *
     * @var LoginAttemptService
     */
    protected $loginAttemptService;

    /**
     * Lidnr.
     *
     * @var string
     */
    protected $lidnr;

    /**
     * Pincode.
     *
     * @var string
     */
    protected $pincode;

    /**
     * Constructor.
     *
     * @param LegacyService
     */
    public function __construct(LegacyService $legacyService, loginAttemptService $loginAttemptService)
    {
        $this->legacyService = $legacyService;
        $this->loginAttemptService = $loginAttemptService;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate()
    {
        $user = $this->mapper->findByLogin($this->lidnr);

        if (null === $user) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
                []
            );
        }

        if ($this->loginAttemptService->loginAttemptsExceeded(LoginAttempt::TYPE_PIN, $user)) {
            return new Result(
                Result::FAILURE,
                null,
                []
            );
        }

        if (!$this->verifyPincode($user)) {
            $this->loginAttemptService->logFailedLogin($user, LoginAttempt::TYPE_PIN);

            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
                []
            );
        }

        /**
         * Users logging in in this way should not have all their regular roles. Since this login
         * method is less secure.
         */
        $userRole = new UserRoleModel();
        $userRole->setRole('sosuser');
        $userRole->setLidnr($this->lidnr);
        $user->setRoles([$userRole]);

        return new Result(Result::SUCCESS, $user);
    }

    /**
     * Verify the password.
     *
     * @return bool
     */
    protected function verifyPincode(UserModel $user)
    {
        return $this->legacyService->checkPincode($user, $this->pincode);
    }

    /**
     * Set the credentials.
     *
     * @param string $lidnr
     * @param string $pincode
     */
    public function setCredentials($lidnr, $pincode)
    {
        $this->lidnr = $lidnr;
        $this->pincode = $pincode;
    }

    /**
     * Get the mapper.
     *
     * @return UserMapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Set the mapper.
     */
    public function setMapper(UserMapper $mapper)
    {
        $this->mapper = $mapper;
    }
}
