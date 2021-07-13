<?php

namespace User\Service;

use Application\Service\AbstractAclService;
use User\Form\ApiToken;
use User\Model\ApiUser as ApiUserModel;
use User\Mapper\ApiUser as ApiUserMapper;
use User\Permissions\NotAllowedException;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;

/**
 * API User service.
 */
class ApiUser extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var \User\Model\User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var ApiUserMapper
     */
    private $apiUserMapper;

    /**
     * @var ApiToken
     */
    private $apiTokenForm;

    public function __construct(Translator $translator, $userRole, Acl $acl, ApiUserMapper $apiUserMapper, ApiToken $apiTokenForm)
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->apiUserMapper = $apiUserMapper;
        $this->apiTokenForm = $apiTokenForm;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Identity storage.
     *
     * @var ApiUserModel
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
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view API tokens')
            );
        }
        return $this->apiUserMapper->findAll();
    }

    /**
     * Remove a token by it's ID
     *
     * @param int $id
     */
    public function removeToken($id)
    {
        if (!$this->isAllowed('remove')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to remove API tokens')
            );
        }
        $this->apiUserMapper->remove($id);
    }

    /**
     * Obtain a token by it's ID
     *
     * @param int $id
     *
     * @return ApiUserModel Token
     */
    public function getToken($id)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view API tokens')
            );
        }
        return $this->apiUserMapper->find($id);
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

        $this->apiUserMapper->persist($apiUser);

        return $apiUser;
    }

    /**
     * Verify and save an API token.
     *
     * @param string $token
     */
    public function verifyToken($token)
    {
        $mapper = $this->apiUserMapper;

        $this->identity = $mapper->findByToken($token);
    }

    /**
     * Generate a token.
     *
     * @return string
     */
    public static function generateToken()
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
     * @return ApiToken
     */
    public function getApiTokenForm()
    {
        if (!$this->isAllowed('add')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to add API tokens')
            );
        }
        return $this->apiTokenForm;
    }

    /**
     * Get the user ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
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
