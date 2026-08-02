<?php

declare(strict_types=1);

namespace App\ViewModel\User\Admin;

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
        public bool $disabled,
    ) {
    }

    public static function fromCompanyUser(CompanyUser $companyUser): self
    {
        return new self(
            id: (int) $companyUser->getId(),
            companyName: $companyUser->getCompany()->getName(),
            representativeName: $companyUser->getName(),
            representativeEmail: $companyUser->getEmail(),
            mfaEnabled: $companyUser->isTotpAuthenticationEnabled(),
            disabled: $companyUser->isDisabled(),
        );
    }
}
