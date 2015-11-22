<?php

namespace User\Service;

use Application\Service\AbstractService;

use User\Model\ApiUser as ApiUserModel;
use User\Mapper\ApiUser as ApiUserMapper;


/**
 * API User service.
 */
class ApiUser extends AbstractService
{

    /**
     * Identity storage.
     *
     * @var ApiUserMapper
     */
    protected $identity;


    /**
     * Obtain all tokens.
     */
    public function getTokens()
    {
        return $this->getApiUserMapper()->findAll();
    }

    /**
     * Verify and save an API token.
     *
     * @param string $token
     */
    public function verifyToken($token)
    {
        $mapper = $this->getApiUserMapper();

        $this->identity = $mapper->findByToken($token);
    }

    /**
     * Check if this service has an identity.
     *
     * @return boolean
     */
    public function hasIdentity()
    {
        return null !== $this->identity;
    }

    /**
     * Get the API User mapper.
     *
     * @return ApiUserMapper
     */
    public function getApiUserMapper()
    {
        return $this->getServiceManager()->get('user_mapper_apiuser');
    }
}
