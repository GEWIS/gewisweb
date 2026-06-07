<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use Locale as IntlLocale;
use Symfony\Component\Translation\TranslatableMessage;

use function array_map;
use function array_merge;
use function explode;

/**
 * This enum contains the different languages that are supported by the website.
 *
 * This is directly related to contents of decisions from GEWISDB. Be careful when making changes here and not there.
 *
 * LangParam is the format compatible with $translator->translate(locale: $langparam)
 * Locale should be compatible with dates.
 *
 * @psalm-type LangParam = 'en'|'nl'
 * @psalm-type Locale = 'en_GB'|'nl_NL'
 */
enum Languages: string
{
    case English = 'english_greatbritain';
    case Dutch = 'dutch_netherlands';

    /**
     * Get the language param ('en', 'nl') from a language
     * An explode is not possible because of psalm
     *
     * @return LangParam
     */
    public function getLangParam(): string
    {
        return match ($this) {
            self::English => 'en',
            self::Dutch => 'nl',
        };
    }

    /**
     * Get the locale ('en_GB', 'nl_NL') from a language
     *
     * @return Locale
     */
    public function getLocale(): string
    {
        return match ($this) {
            self::English => 'en_GB',
            self::Dutch => 'nl_NL',
        };
    }

    /**
     * Get the language from a language param ('en', 'nl')
     *
     * @param LangParam $langParam
     */
    public static function fromLangParam(string $langParam): Languages
    {
        return match ($langParam) {
            'en' => self::English,
            'nl' => self::Dutch,
        };
    }

    /**
     * Get the language from a locale ('en_GB', 'nl_NL')
     *
     * @param Locale $locale
     */
    public static function fromLocale(string $locale): Languages
    {
        return self::fromLangParam(explode('_', $locale)[0]);
    }

    /**
     * The language of the current request, derived from the active locale. The intl locale is a plain string, so the
     * narrowing to a supported language happens here, once, instead of at every call site.
     */
    public static function current(): Languages
    {
        // The intl locale is a plain string, so match it here rather than coerce it into the strict fromLangParam():
        // fall back to English for anything but Dutch, matching the behaviour the call sites previously hand-rolled.
        return match (IntlLocale::getDefault()) {
            'nl', 'nl_NL' => self::Dutch,
            default => self::English,
        };
    }

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::English => new TranslatableMessage('English'),
            self::Dutch => new TranslatableMessage('Dutch'),
        };
    }

    /**
     * @return array<array-key, Languages|string>
     */
    public static function values(): array
    {
        return array_merge(
            array_map(
                static fn (self $language) => $language->getLangParam(),
                self::cases(),
            ),
            self::cases(),
        );
    }

    /**
     * @return string[]
     */
    public static function stringValues(): array
    {
        return array_map(
            static fn (self $language) => $language->getLangParam(),
            self::cases(),
        );
    }
}
