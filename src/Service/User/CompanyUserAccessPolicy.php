<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\CompanyUser;
use App\Repository\Career\CompanyPackageRepository;
use DateTimeImmutable;

/**
 * Decides whether a representative may use the careers portal at all. Two things can take that away: the board shutting
 * the representative out, and the company's contract running out. Neither is scheduled or flagged anywhere; both are
 * worked out from the current state whenever it matters, at sign-in and on every request of a session that is already
 * running.
 */
final readonly class CompanyUserAccessPolicy
{
    public function __construct(
        private CompanyPackageRepository $companyPackageRepository,
    ) {
    }

    public function isAllowed(
        CompanyUser $companyUser,
        DateTimeImmutable $now,
    ): bool {
        if ($companyUser->isDisabled()) {
            return false;
        }

        return $this->companyPackageRepository->hasNonExpiredPackage(
            $companyUser->getCompany(),
            $now,
        );
    }
}
