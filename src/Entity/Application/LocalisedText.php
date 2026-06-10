<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Traits\IdentifiableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use InvalidArgumentException;

/**
 * Class LocalisedText: stores Dutch and English versions of text fields.
 *
 * @psalm-type LocalisedTextGdprArrayType = array{
 *     valueEN: ?string,
 *     valueNL: ?string,
 * }
 */
abstract class LocalisedText
{
    use IdentifiableTrait;

    /**
     * Final so every concrete subclass shares this exact signature, which lets {@see self::copy()} safely use
     * `new static()` (no subclass can introduce extra required constructor arguments).
     */
    final public function __construct(
        #[Column(
            type: Types::TEXT,
            nullable: true,
        )]
        protected ?string $valueEN = null,
        #[Column(
            type: Types::TEXT,
            nullable: true,
        )]
        protected ?string $valueNL = null,
    ) {
    }

    /**
     * A fresh, unpersisted copy of this localised text (same concrete subtype, no id), for deep-copying revision
     * content so orphan-removal can never delete the source revision's row.
     */
    public function copy(): static
    {
        return new static(
            $this->valueEN,
            $this->valueNL,
        );
    }

    public function getValueEN(): ?string
    {
        return $this->valueEN;
    }

    public function getValueNL(): ?string
    {
        return $this->valueNL;
    }

    public function updateValues(
        ?string $valueEN,
        ?string $valueNL,
    ): void {
        $this->updateValueEN($valueEN);
        $this->updateValueNL($valueNL);
    }

    public function updateValueEN(?string $valueEN): void
    {
        $this->valueEN = $valueEN;
    }

    public function updateValueNL(?string $valueNL): void
    {
        $this->valueNL = $valueNL;
    }

    /**
     * @return string|null the localised text or null when there is no localised text
     *
     * @throws InvalidArgumentException
     */
    public function getText(Languages $locale): ?string
    {
        return match ($locale) {
            Languages::Dutch => null !== $this->valueNL ? $this->valueNL : $this->valueEN,
            Languages::English => null !== $this->valueEN ? $this->valueEN : $this->valueNL,
        };
    }

    /**
     * @return string|null the localised text
     *
     * @throws InvalidArgumentException
     */
    public function getExactText(Languages $locale): ?string
    {
        return match ($locale) {
            Languages::Dutch => $this->valueNL,
            Languages::English => $this->valueEN,
        };
    }

    /**
     * @return LocalisedTextGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'valueEN' => $this->getValueEN(),
            'valueNL' => $this->getValueNL(),
        ];
    }
}
