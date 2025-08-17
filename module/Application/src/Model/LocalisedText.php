<?php

declare(strict_types=1);

namespace Application\Model;

use Application\Model\Enums\Languages;
use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\Column;
use InvalidArgumentException;
use Laminas\Session\Container as SessionContainer;

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

    public function __construct(
        #[Column(
            type: 'text',
            nullable: true,
        )]
        protected ?string $valueEN = null,
        #[Column(
            type: 'text',
            nullable: true,
        )]
        protected ?string $valueNL = null,
    ) {
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
    public function getText(?Languages $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->getPreferredLocale();
        } else {
            $locale = $locale->getLangParam();
        }

        return match ($locale) {
            Languages::Dutch->getLangParam() => $this->valueNL ?? $this->valueEN,
            Languages::English->getLangParam() => $this->valueEN ?? $this->valueNL,
            default => throw new InvalidArgumentException('Locale not supported: ' . $locale),
        };
    }

    /**
     * @psalm-return 'en'|'nl' - From {@link Languages::getLangParam()}.
     */
    private function getPreferredLocale(): string
    {
        $langSession = new SessionContainer('lang');

        return $langSession->lang;
    }

    /**
     * @return string|null the localised text
     *
     * @throws InvalidArgumentException
     */
    public function getExactText(?Languages $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->getPreferredLocale();
        } else {
            $locale = $locale->getLangParam();
        }

        return match ($locale) {
            Languages::Dutch->getLangParam() => $this->valueNL,
            Languages::English->getLangParam() => $this->valueEN,
            default => throw new InvalidArgumentException('Locale not supported: ' . $locale),
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
