<?php

declare(strict_types=1);

namespace App\View\User\Admin;

use App\Entity\User\CompanyUser;

/**
 * Read-model view of a {@see CompanyUser} row for the admin company-users overview.
 */
final readonly class CompanyUserRow
{
    public function __construct(
        public int $id,
        public string $companyName,
        public string $representativeName,
        public string $representativeEmail,
        public bool $mfaEnabled,
    ) {
    }

    public static function fromCompanyUser(CompanyUser $companyUser): self
    {
        $company = $companyUser->getCompany();

        return new self(
            id: (int) $companyUser->getId(),
            companyName: $company->getName(),
            representativeName: $company->getRepresentativeName(),
            representativeEmail: $company->getRepresentativeEmail(),
            mfaEnabled: $companyUser->isTotpAuthenticationEnabled(),
        );
    }
}
