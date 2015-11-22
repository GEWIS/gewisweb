<?php

namespace User\Service;

use Application\Service\AbstractAclService;

use User\Model\ApiUser as ApiUserModel;
use User\Mapper\ApiUser as ApiUserMapper;


/**
 * API User service.
 */
class ApiUser extends AbstractAclService
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
        if (!$this->isAllowed('list')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view API tokens')
            );
        }
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
     * Get the user ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('acl');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    public function getDefaultResourceId()
    {
        return 'apiuser';
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
