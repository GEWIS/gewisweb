<?php

namespace Application\Hydrator\Strategy;

use Application\Model\Enums\Languages;
use Laminas\Hydrator\Strategy\StrategyInterface;

class LanguageHydratorStrategy implements StrategyInterface
{
    public function extract(
        $value,
        ?object $object = null,
    ): string {
        if ($value instanceof Languages) {
            return $value->value;
        }

        return Languages::from($value)->value;
    }

    public function hydrate(
        $value,
        ?array $data,
    ): Languages {
        if ($value instanceof Languages) {
            return $value;
        }

        return Languages::from($value);
    }
}
