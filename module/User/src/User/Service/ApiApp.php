<?php


namespace User\Service;

use Application\Service\AbstractAclService;
use User\Mapper\ApiApp as ApiAppMapper;
use User\Model\User;

class ApiApp extends AbstractAclService
{

    /**
     * @var ApiAppMapper
     */
    protected $mapper;

    /**
     * Get a callback from an appId and a user identity
     * @param string $appId
     * @param User $user
     * @return string
     */
    public function callbackWithToken($appId, User $user)
    {
        $app = $this->getMapper()->findByAppId($appId);

        // TODO: create JWT token
        $token = '';

        return $app->getCallback() . '?token=' . $token;
    }

    /**
     * @return ApiAppMapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }
}