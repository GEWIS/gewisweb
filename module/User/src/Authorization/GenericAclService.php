<?php


namespace User\Authorization;


use Application\Service\AbstractAclService;
use Laminas\I18n\Translator\TranslatorInterface;
use User\Authentication\AuthenticationService;
use User\Model\User;
use User\Permissions\NotAllowedException;

abstract class GenericAclService extends AbstractAclService
{
    private TranslatorInterface $translator;
    private AuthenticationService $authService;
    private string $remoteAddress;
    private string $tueRange;

    public function __construct (
        TranslatorInterface $translator,
        AuthenticationService $authService,
        string $remoteAddress,
        string $tueRange
    ) {
        $this->translator = $translator;
        $this->authService = $authService;
        $this->remoteAddress = $remoteAddress;
        $this->tueRange = $tueRange;
    }

    /**
     * @inheritDoc
     */
    protected function getRole()
    {
        if ($this->authService->hasIdentity()) {
            return $this->authService->getIdentity();
        }

        // TODO: Refactor and re-enable the ApiUser service after a circular dependency has been removed.
        // Possibly extend the LaminasAuthService ?
//        if ($this->apiUserService->hasIdentity()) {
//            return 'apiuser';
//        }

        if (0 === strpos($this->remoteAddress, $this->tueRange)) {
            return 'tueguest';
        }

        return 'guest';
    }

    /**
     * Gets the user identity, or gives a 403 if the user is not logged in
     *
     * @return User the current logged in user
     * @throws NotAllowedException if no user is logged in
     */
    public function getIdentityOrThrowException()
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
