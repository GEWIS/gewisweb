<?php

declare(strict_types=1);

namespace App\Entity\User\Enums;

use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use ValueError;

use function is_a;
use function sprintf;

enum UserTypes: string
{
    case CompanyUser = 'company_user';
    case User = 'user';

    public static function fromClass(string $fqcn): self
    {
        return match (true) {
            is_a(
                $fqcn,
                CompanyUser::class,
                true,
            ) => self::CompanyUser,
            is_a(
                $fqcn,
                User::class,
                true,
            ) => self::User,
            default => throw new ValueError(sprintf('No UserType mapped for class "%s".', $fqcn)),
        };
    }
}
