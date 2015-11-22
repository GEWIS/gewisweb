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
     * Verify an API token.
     *
     * @param string $token
     *
     * @return boolean
     */
    public function verifyToken($token)
    {
        $mapper = $this->getApiUserMapper();

        return null !== $mapper->findByToken($token);
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
