<?php

declare(strict_types=1);

namespace Education\Hydrator\Strategy;

use Education\Model\Enums\ExamTypes;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Override;

class ExamTypeHydratorStrategy implements StrategyInterface
{
    #[Override]
    public function extract(
        mixed $value,
        ?object $object = null,
    ): string {
        if ($value instanceof ExamTypes) {
            return $value->value;
        }

        return ExamTypes::from($value)->value;
    }

    #[Override]
    public function hydrate(
        mixed $value,
        ?array $data,
    ): ExamTypes {
        if ($value instanceof ExamTypes) {
            return $value;
        }

        return ExamTypes::from($value);
    }
}
