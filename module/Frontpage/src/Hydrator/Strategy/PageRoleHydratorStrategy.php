<?php

declare(strict_types=1);

namespace Frontpage\Hydrator\Strategy;

use Laminas\Hydrator\Strategy\StrategyInterface;
use User\Model\Enums\UserRoles;

class PageRoleHydratorStrategy implements StrategyInterface
{
    public function extract(
        mixed $value,
        ?object $object = null,
    ): string {
        if ($value instanceof UserRoles) {
            return $value->value;
        }

        return UserRoles::from($value)->value;
    }

    public function hydrate(
        mixed $value,
        ?array $data,
    ): UserRoles {
        if ($value instanceof UserRoles) {
            return $value;
        }

        return UserRoles::from($value);
    }
}
