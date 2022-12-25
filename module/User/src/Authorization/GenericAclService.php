<?php

namespace User\Authorization;

use Application\Model\IdentityInterface;
use Application\Service\AbstractAclService;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Role\RoleInterface;
use User\Authentication\{
    Adapter\CompanyUserAdapter,
    Adapter\UserAdapter,
    ApiAuthenticationService,
    AuthenticationService as CompanyUserAuthenticationService,
    AuthenticationService as UserAuthenticationService,
    Storage\CompanyUserSession,
    Storage\UserSession,
};
use User\Model\{
    CompanyUser as CompanyUserModel,
    User as UserModel,
};
use User\Permissions\NotAllowedException;

abstract class GenericAclService extends AbstractAclService
{
    private array $checkedIps = [];

    /**
     * @psalm-param UserAuthenticationService<UserSession, UserAdapter> $userAuthService
     * @psalm-param CompanyUserAuthenticationService<CompanyUserSession, CompanyUserAdapter> $companyUserAuthService
     */
    public function __construct(
        private readonly Translator $translator,
        private readonly UserAuthenticationService $userAuthService,
        private readonly CompanyUserAuthenticationService $companyUserAuthService,
        private readonly ApiAuthenticationService $apiUserAuthService,
        private readonly array $tueRanges,
        private readonly string $remoteAddress,
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
        if (null !== ($identity = $this->getIdentity())) {
            return $identity;
        }

        if ($this->fromTueNetwork()) {
            return 'tueguest';
        }

        return 'guest';
    }

    public function getIdentity(): ?IdentityInterface
    {
        if ($this->userAuthService->hasIdentity()) {
            return $this->userAuthService->getIdentity();
        }

        if ($this->companyUserAuthService->hasIdentity()) {
            return $this->companyUserAuthService->getIdentity();
        }

        if ($this->apiUserAuthService->hasIdentity()) {
            return $this->apiUserAuthService->getIdentity();
        }

        return null;
    }

    /**
     * Get the company user identity or `null` if no `CompanyUser` is logged in.
     */
    public function getCompanyUserIdentity(): ?CompanyUserModel
    {
        return $this->companyUserAuthService->getIdentity();
    }

    /**
     * Gets the company user identity or gives a 403 if the company user is not logged in.
     *
     * @throws NotAllowedException
     */
    public function getCompanyUserIdentityOrThrowException(): CompanyUserModel
    {
        if (null !== ($identity = $this->getCompanyUserIdentity())) {
            return $identity;
        }

        throw new NotAllowedException(
            $this->translator->translate('You are not allowed to perform this action. If you are not logged in please do so before continuing. If you are already logged in this action may require you to login in with a different account.')
        );
    }

    /**
     * Get the user identity or `null` if no `User` is logged in.
     */
    public function getUserIdentity(): ?UserModel
    {
        return $this->userAuthService->getIdentity();
    }

    /**
     * Gets the user identity or gives a 403 if the user is not logged in.
     *
     * @throws NotAllowedException
     */
    public function getUserIdentityOrThrowException(): UserModel
    {
        if (null !== ($identity = $this->getUserIdentity())) {
            return $identity;
        }

        throw new NotAllowedException(
            $this->translator->translate('You are not allowed to perform this action. If you are not logged in please do so before continuing. If you are already logged in this action may require you to login in with a different account.')
        );
    }

    /**
     * Check whether the remote address (as returned by the proxy) comes from a TU/e network. Networks are provided in
     * CIDR notation.
     */
    private function fromTueNetwork(): bool
    {
        // If we already checked the in the past, we do not need to do it again.
        if (isset($this->checkedIps[$this->remoteAddress])) {
            return $this->checkedIps[$this->remoteAddress];
        }

        // We only accept and expect IPv4 addresses.
        if (!filter_var($this->remoteAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->checkedIps[$this->remoteAddress] = false;
        }

        if (!empty($this->tueRanges)) {
            foreach ($this->tueRanges as $range) {
                // Ensure that we actually check against a range.
                if (!str_contains($range, '/')) {
                    continue;
                }

                [$subnet, $bits] = explode('/', $range, 2);
                $bits = (int) $bits;

                // Ensure that the subnet is valid.
                if (
                    0 > $bits
                    || 32 < $bits
                    || false === ip2long($subnet)
                ) {
                    continue;
                }

                // Precompute the netmask to be able to re-align the range (if necessary) and check the remote address.
                $netmask = -1 << (32 - $bits);
                if ((ip2long($subnet) & $netmask) === (ip2long($this->remoteAddress) & $netmask)) {
                    return $this->checkedIps[$this->remoteAddress] = true;
                }
            }
        }

        return $this->checkedIps[$this->remoteAddress] = false;
    }
}
