<?php

declare(strict_types=1);

namespace Application\Hydrator\Strategy;

use Application\Model\Enums\Languages;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Override;

class LanguageHydratorStrategy implements StrategyInterface
{
    #[Override]
    public function extract(
        mixed $value,
        ?object $object = null,
    ): string {
        if ($value instanceof Languages) {
            return $value->getLangParam();
        }

        return Languages::fromLangParam($value)->getLangParam();
    }

    #[Override]
    public function hydrate(
        mixed $value,
        ?array $data,
    ): Languages {
        if ($value instanceof Languages) {
            return $value;
        }

        return Languages::fromLangParam($value);
    }
}
