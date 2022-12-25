<?php

namespace Application\View\Helper;

use Laminas\View\Helper\{
    AbstractHelper,
    Identity as LaminasIdentity,
};
use User\Authentication\AuthenticationService;
use User\Model\CompanyUser as CompanyUserModel;

/**
 * View helper plugin which can fetch authenticated company identities. Essentially an extension of
 * {@link LaminasIdentity} to support company users.
 */
class CompanyIdentity extends AbstractHelper
{
    /**
     * @var AuthenticationService
     */
    private AuthenticationService $companyUserAuthService;

    /**
     * @param AuthenticationService $companyUserAuthService
     */
    public function __construct(AuthenticationService $companyUserAuthService) {
        $this->companyUserAuthService = $companyUserAuthService;
    }

    /**
     * @return CompanyUserModel|null
     */
    public function __invoke(): CompanyUserModel|null
    {
        if ($this->companyUserAuthService->hasIdentity()) {
            return $this->companyUserAuthService->getIdentity();
        }

        return null;
    }
}
