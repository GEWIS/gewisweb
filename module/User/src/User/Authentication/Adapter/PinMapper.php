<?php

namespace User\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface,
    Zend\Authentication\Result,
    User\Mapper\User as UserMapper,
    User\Model\User as UserModel;

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
    public function __construct($legacyService)
    {
        $this->legacyService = $legacyService;
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate()
    {
        $mapper = $this->getMapper();

        $user = $mapper->findByLidnr($this->login);

        if (null === $user) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
                []
            );
        }

        if (!$this->verifyPincode($user)) {
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
        //TODO
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
