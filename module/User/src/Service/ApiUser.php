<?php

declare(strict_types=1);

namespace User\Service;

use Doctrine\ORM\Exception\ORMException;
use Laminas\Mvc\I18n\Translator;
use User\Form\ApiToken as ApiTokenForm;
use User\Mapper\ApiUser as ApiUserMapper;
use User\Model\ApiUser as ApiUserModel;
use User\Permissions\NotAllowedException;

use function base64_encode;
use function openssl_random_pseudo_bytes;

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
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Obtain all tokens.
     *
     * @return ApiUserModel[] Of tokens
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
     * @return ApiUserModel|null Token
     */
    public function getToken(int $id): ?ApiUserModel
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
     * @throws ORMException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
     */
    public static function generateToken(): string
    {
        return base64_encode(openssl_random_pseudo_bytes(32));
    }

    /**
     * Get the API token form.
     */
    public function getApiTokenForm(): ApiTokenForm
    {
        if (!$this->aclService->isAllowed('add', 'apiuser')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add API tokens'));
        }

        return $this->apiTokenForm;
    }
}
