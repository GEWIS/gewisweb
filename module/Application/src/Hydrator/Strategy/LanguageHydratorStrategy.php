<?php

declare(strict_types=1);

namespace Application\Hydrator\Strategy;

use Application\Model\Enums\Languages;
use Laminas\Hydrator\Strategy\StrategyInterface;

class LanguageHydratorStrategy implements StrategyInterface
{
    public function extract(
        mixed $value,
        ?object $object = null,
    ): string {
        if ($value instanceof Languages) {
            return $value->value;
        }

        return Languages::from($value)->value;
    }

    public function hydrate(
        mixed $value,
        ?array $data,
    ): Languages {
        if ($value instanceof Languages) {
            return $value;
        }

        return Languages::from($value);
    }
}
