<?php

namespace User\Authorization;

use Application\Service\AbstractAclService;
use Laminas\Mvc\I18n\Translator;
use User\Authentication\{
    ApiAuthenticationService,
    AuthenticationService,
};
use Laminas\Permissions\Acl\Role\RoleInterface;
use User\Model\{
    ApiUser as ApiUserModel,
    CompanyUser as CompanyUserModel,
    User as UserModel,
};
use User\Permissions\NotAllowedException;

abstract class GenericAclService extends AbstractAclService
{
    private array $checkedIps = [];

    public function __construct(
        private readonly Translator $translator,
        private readonly AuthenticationService $userAuthService,
        private readonly AuthenticationService $companyUserAuthService,
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
        if ($this->hasIdentity()) {
            return $this->getIdentity();
        }

        if ($this->fromTueNetwork()) {
            return 'tueguest';
        }

        return 'guest';
    }

    /**
     * Gets the user identity, or gives a 403 if the user is not logged in
     *
     * @return CompanyUserModel|UserModel|null the current logged in user
     */
    public function getIdentityOrThrowException(): CompanyUserModel|UserModel|null
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
     */
    public function getIdentity(): ApiUserModel|CompanyUserModel|UserModel|null
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
     * Checks whether the user is logged in
     *
     * @return bool true if the user is logged in, false otherwise
     */
    public function hasIdentity(): bool
    {
        return $this->userAuthService->hasIdentity()
            || $this->companyUserAuthService->hasIdentity()
            || $this->apiUserAuthService->hasIdentity();
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
