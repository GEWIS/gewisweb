<?php

namespace User\Service;

use Doctrine\ORM\Exception\ORMException;
use Laminas\Mvc\I18n\Translator;
use User\Form\ApiToken as ApiTokenForm;
use User\Mapper\ApiUser as ApiUserMapper;
use User\Model\ApiUser as ApiUserModel;
use User\Permissions\NotAllowedException;

/**
 * API User service.
 */
class ApiUser
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ApiUserMapper $apiUserMapper,
        private readonly ApiTokenForm $apiTokenForm,
    ) {
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Obtain all tokens.
     *
     * @return array Of tokens
     */
    public function getTokens(): array
    {
        if (!$this->aclService->isAllowed('list', 'apiuser')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view API tokens'));
        }

        return $this->apiUserMapper->findAll();
    }

    /**
     * Remove a token by its ID.
     *
     * @param int $id
     *
     * @throws ORMException
     */
    public function removeToken(int $id): void
    {
        if (!$this->aclService->isAllowed('remove', 'apiuser')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to remove API tokens'));
        }

        $this->apiUserMapper->removeById($id);
    }

    /**
     * Obtain a token by its ID.
     *
     * @param int $id
     *
     * @return ApiUserModel Token
     */
    public function getToken(int $id): ApiUserModel
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
     *
     * @return ApiUserModel
     * @throws ORMException
     */
    public function addToken(array $data): ApiUserModel
    {
        $apiUser = new ApiUserModel();

        $apiUser->setName($data['name']);
        $apiUser->setToken($this->generateToken());

        $this->apiUserMapper->persist($apiUser);

        return $apiUser;
    }

    /**
     * Generate a token.
     *
     * @return string
     */
    public static function generateToken(): string
    {
        return base64_encode(openssl_random_pseudo_bytes(32));
    }

    /**
     * Get the API token form.
     *
     * @return ApiTokenForm
     */
    public function getApiTokenForm(): ApiTokenForm
    {
        if (!$this->aclService->isAllowed('add', 'apiuser')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add API tokens'));
        }

        return $this->apiTokenForm;
    }
}
