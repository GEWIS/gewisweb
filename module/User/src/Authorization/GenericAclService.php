<?php

namespace User\Authorization;

use Application\Service\AbstractAclService;
use Laminas\Mvc\I18n\Translator;
use User\Authentication\{
    ApiAuthenticationService,
    AuthenticationService,
};
use Laminas\Permissions\Acl\Role\RoleInterface;
use User\Model\User;
use User\Permissions\NotAllowedException;

abstract class GenericAclService extends AbstractAclService
{
    public function __construct(
        private readonly Translator $translator,
        private readonly AuthenticationService $authService,
        private readonly ApiAuthenticationService $apiAuthService,
        private readonly string $remoteAddress,
        private readonly string $tueRange,
    ) {
    }

    /**
     * @inheritDoc
     *
     * The role that should take precedence should be returned first.
     * This is because of the behaviour of {@link \Laminas\Permissions\Acl\Role\Registry}.
     */
    protected function getRole(): RoleInterface|string
    {
        if ($this->authService->hasIdentity()) {
            return $this->authService->getIdentity();
        }

        if ($this->apiAuthService->hasIdentity()) {
            return $this->apiAuthService->getIdentity();
        }

        // TODO: We could create an assertion for this.
        if (str_starts_with($this->remoteAddress, $this->tueRange)) {
            return 'tueguest';
        }

        return 'guest';
    }

    /**
     * Gets the user identity, or gives a 403 if the user is not logged in
     *
     * @return User|null the current logged in user
     */
    public function getIdentityOrThrowException(): ?User
    {
        if (!$this->hasIdentity()) {
            throw new NotAllowedException(
                $this->translator->translate('You need to log in to perform this action')
            );
        }
        return $this->getIdentity();
    }

    /**
     * Gets the user identity if logged in or null otherwise
     *
     * @return User|null the current logged-in user
     */
    public function getIdentity(): ?User
    {
        return $this->authService->getIdentity();
    }

    /**
     * Checks whether the user is logged in
     *
     * @return bool true if the user is logged in, false otherwise
     */
    public function hasIdentity(): bool
    {
        return !is_null($this->getIdentity());
    }
}
