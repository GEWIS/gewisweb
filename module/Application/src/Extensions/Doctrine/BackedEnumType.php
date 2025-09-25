<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine;

use BackedEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

use function call_user_func;

/**
 * Custom mapping type for Doctrine DBAL to directly support enums in a database without having to use a native type.
 *
 * It is necessary to use this custom mapping type due to an apparent bug in the value conversion layer in DBAL when
 * using trying to construct our (Sub)Decisions in specific scenarios (e.g. seeding the database).
 *
 * Due to the `final` marking of the constructor we cannot initialise {@link BackedEnumType::$enumClass} and
 * {@link BackedEnumType::$name}. As such, we need to override these when creating specific mapping types.
 *
 * @template T of BackedEnum
 */
abstract class BackedEnumType extends Type
{
    /**
     * @var class-string<T> $enumClass
     * @required
     */
    public string $enumClass;

    /**
     * @required
     */
    public const string NAME = '';

    /**
     * {@inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getSQLDeclaration(
        array $column,
        AbstractPlatform $platform,
    ): string {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    /**
     * @return T|null
     */
    public function convertToPHPValue(
        mixed $value,
        AbstractPlatform $platform,
    ) {
        if (empty($value)) {
            return null;
        }

        return call_user_func([$this->enumClass, 'from'], $value);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function convertToDatabaseValue(
        mixed $value,
        AbstractPlatform $platform,
    ) {
        return $value instanceof $this->enumClass ? $value->value : $value;
    }
}
