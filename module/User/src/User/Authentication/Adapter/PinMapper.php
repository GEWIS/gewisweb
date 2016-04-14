<?php

namespace User\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface,
    Zend\Authentication\Result,
    User\Mapper\User as UserMapper,
    User\Model\User as UserModel,
    User\Model\UserRole as UserRoleModel,
    User\Model\LoginAttempt;
use Application\Service\Legacy as LegacyService;
use User\Service\User as UserService;

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
     * @var \Application\Service\Legacy
     */
    protected $legacyService;

    /**
     * User Service
     * (for logging failed login attempts)
     *
     * @var UserService
     */
    protected $userService;

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
     * @param \Application\Service\Legacy
     */
    public function __construct(LegacyService $legacyService, UserService $userService)
    {
        $this->legacyService = $legacyService;
        $this->userService = $userService;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate()
    {
        $mapper = $this->getMapper();

        $user = $mapper->findByLogin($this->lidnr);

        if (null === $user) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
                []
            );
        }

        if ($this->userService->loginAttemptsExceeded(LoginAttempt::TYPE_PIN, $user)) {
            return new Result(
                Result::FAILURE,
                null,
                []
            );
        }

        if (!$this->verifyPincode($user)) {
            $this->userService->logFailedLogin($user, LoginAttempt::TYPE_PIN);
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
     * @param UserModel $user
     *
     * @return boolean
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
     *
     * @param UserMapper $mapper
     */
    public function setMapper(UserMapper $mapper)
    {
        $this->mapper = $mapper;
    }
}
