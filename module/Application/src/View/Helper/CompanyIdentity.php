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
    public function __construct(private readonly AuthenticationService $companyUserAuthService)
    {
    }

    /**
     * @return CompanyUserModel|null
     */
    public function __invoke(): CompanyUserModel|null
    {
        $identity = $this->companyUserAuthService->getIdentity();
        if ($identity instanceof CompanyUserModel) {
            return $identity;
        }

        return null;
    }
}
