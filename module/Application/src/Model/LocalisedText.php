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
    public function getText(?string $locale = null): ?string
    {
        if (null === $locale) {
            $locale = $this->getPreferredLocale();
        }

        return match ($locale) {
            'nl' => null !== $this->valueNL ? $this->valueNL : $this->valueEN,
            'en' => null !== $this->valueEN ? $this->valueEN : $this->valueNL,
            default => throw new InvalidArgumentException('Locale not supported: ' . $locale),
        };
    }

    /**
     * @return string the preferred language: either 'nl'  or 'en'
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
            $locale = $locale->value;
        }

        return match ($locale) {
            'nl' => $this->valueNL,
            'en' => $this->valueEN,
            default => throw new InvalidArgumentException('Locale not supported: ' . $locale),
        };
    }
}
