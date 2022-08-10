<?php

namespace User\Authorization;

use Application\Service\AbstractAclService;
use Laminas\Mvc\I18n\Translator;
use User\Authentication\{
    ApiAuthenticationService,
    AuthenticationService,
};
use User\Model\{ApiUser, User};
use User\Permissions\NotAllowedException;

abstract class GenericAclService extends AbstractAclService
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var AuthenticationService
     */
    private AuthenticationService $authService;

    /**
     * @var ApiAuthenticationService
     */
    private ApiAuthenticationService $apiAuthService;

    /**
     * @var string
     */
    private string $remoteAddress;

    /**
     * @var string
     */
    private string $tueRange;

    public function __construct(
        Translator $translator,
        AuthenticationService $authService,
        ApiAuthenticationService $apiAuthService,
        string $remoteAddress,
        string $tueRange,
    ) {
        $this->translator = $translator;
        $this->authService = $authService;
        $this->apiAuthService = $apiAuthService;
        $this->remoteAddress = $remoteAddress;
        $this->tueRange = $tueRange;
    }

    /**
     * @inheritDoc
 * The role that should take precedence should be returned first.
     * This is because of the behaviour of {@link \Laminas\Permissions\Acl\Role\Registry}.
     *
     * @return User|ApiUser|string
     *
     * @psalm-return 'guest'|'tueguest'|User|ApiUser
     */
    protected function getRole(): string|User|ApiUser
    {
        if ($this->authService->hasIdentity()) {
            $identity = $this->authService->getIdentity();
            if (!is_null($identity)) {
                return $identity;
            }
        }

        if ($this->apiAuthService->hasIdentity()) {
            $identity = $this->apiAuthService->getIdentity();
            if (!is_null($identity)) {
                return $identity;
            }
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
     * @return User|null the current logged in user
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
