<?php

declare(strict_types=1);

namespace App\Message\User;

use App\Entity\User\Enums\UserTypes;

class PasswordResetRequestEmail
{
    public function __construct(
        private readonly UserTypes $userType,
        private readonly string $email,
        private readonly ?int $membershipNumber = null,
    ) {
    }

    public function getUserType(): UserTypes
    {
        return $this->userType;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMembershipNumber(): ?int
    {
        return $this->membershipNumber;
    }
}
