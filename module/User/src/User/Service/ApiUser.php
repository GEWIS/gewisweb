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
     *
     * @return array Of tokens
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
     * Remove a token by it's ID
     *
     * @param int $id
     */
    public function removeToken($id)
    {
        if (!$this->isAllowed('remove')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to remove API tokens')
            );
        }
        $this->getApiUserMapper()->remove($id);
    }

    /**
     * Obtain a token by it's ID
     *
     * @param int $id
     *
     * @return User\Model\ApiUser Token
     */
    public function getToken($id)
    {
        if (!$this->isAllowed('view')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view API tokens')
            );
        }
        return $this->getApiUserMapper()->find($id);
    }

    /**
     * Add an API token.
     *
     * @param array $data
     */
    public function addToken($data)
    {
        $form = $this->getApiTokenForm();

        $form->setData($data);

        $form->bind(new ApiUserModel());

        if (!$form->isValid()) {
            return false;
        }

        $apiUser = $form->getData();
        $apiUser->setToken($this->generateToken());

        $this->getApiUserMapper()->persist($apiUser);

        return $apiUser;
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
     * Generate a token.
     *
     * @return string
     */
    public function generateToken()
    {
        return base64_encode(openssl_random_pseudo_bytes(32));
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
     * Get the API token form
     *
     * @return \User\Form\ApiToken
     */
    public function getApiTokenForm()
    {
        if (!$this->isAllowed('add')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to add API tokens')
            );
        }
        return $this->getServiceManager()->get('user_form_apitoken');
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

}
