<?php

namespace User\Service;

use Laminas\Mvc\I18n\Translator;
use User\Form\ApiToken;
use User\Mapper\ApiUser as ApiUserMapper;
use User\Model\ApiUser as ApiUserModel;
use User\Permissions\NotAllowedException;

/**
 * API User service.
 */
class ApiUser
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ApiUserMapper
     */
    private $apiUserMapper;

    /**
     * @var ApiToken
     */
    private $apiTokenForm;

    private AclService $aclService;

    public function __construct(
        Translator $translator,
        ApiUserMapper $apiUserMapper,
        ApiToken $apiTokenForm,
        AclService $aclService
    ) {
        $this->translator = $translator;
        $this->apiUserMapper = $apiUserMapper;
        $this->apiTokenForm = $apiTokenForm;
        $this->aclService = $aclService;
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
        if (!$this->aclService->isAllowed('list', 'apiuser')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view API tokens'));
        }

        return $this->apiUserMapper->findAll();
    }

    /**
     * Remove a token by it's ID.
     *
     * @param int $id
     */
    public function removeToken($id)
    {
        if (!$this->aclService->isAllowed('remove', 'apiuser')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to remove API tokens'));
        }
        $this->apiUserMapper->remove($id);
    }

    /**
     * Obtain a token by it's ID.
     *
     * @param int $id
     *
     * @return ApiUserModel Token
     */
    public function getToken($id)
    {
        if (!$this->aclService->isAllowed('view', 'apiuser')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view API tokens'));
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
     * Get the API token form.
     *
     * @return ApiToken
     */
    public function getApiTokenForm()
    {
        if (!$this->aclService->isAllowed('add', 'apiuser')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add API tokens'));
        }

        return $this->apiTokenForm;
    }
}
