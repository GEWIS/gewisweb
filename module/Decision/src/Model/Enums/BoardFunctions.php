<?php

declare(strict_types=1);

namespace Decision\Model\Enums;

use Application\Model\Enums\Languages;
use Laminas\Mvc\I18n\DummyTranslator;
use Laminas\Mvc\I18n\Translator;

use function array_combine;
use function array_filter;
use function array_map;
use function in_array;

/**
 * Enum with board functions
 * The values are in Dutch, because decisions are made in Dutch and thus this value is guaranteed to not change
 */
enum BoardFunctions: string
{
    /** Current functions */
    case Chair = 'Voorzitter';
    case Secretary = 'Secretaris';
    case Treasurer = 'Penningmeester';
    case Education = 'Commissaris Onderwijs';
    case ExternalAffairs = 'Commissaris Externe Betrekkingen';
    case InternalAffairs = 'Commissaris Interne Betrekkingen';

    /** Legacy functions */
    case LegacyEducation = 'Onderwijscommissaris';
    case PrOfficer = 'PR-Functionaris';
    case ViceChair = 'Vice-Voorzitter';

    /** One-off functions */
    case BrandManager = 'Brand Manager';
    case CareerdevelopmentExternalAffairs = 'Commissaris Carrièreontwikkeling en Externe Betrekkingen';
    case DigitalInfrastructure = 'Commissaris Digitale Infrastructuur';
    case Innovation = 'Commissaris Innovatie';
    case Information = 'Commissaris Kennisbeheer';
    case Community = 'Commissaris Verenigingsontwikkeling';

    public function isLegacy(): bool
    {
        return !in_array($this, [
            self::Chair,
            self::Secretary,
            self::Treasurer,
            self::Education,
            self::ExternalAffairs,
            self::InternalAffairs,
            self::Community,
        ]);
    }

    /**
     * Give the function name with the given translation. If no translator is given, we return the default language.
     */
    public function getName(
        ?Translator $translator,
        ?Languages $language = null,
    ): string {
        if (null === $translator) {
            $translator = new DummyTranslator();
        }

        return match ($this) {
            self::Chair => $translator->translate('Chair', locale: $language?->getLangParam()),
            self::Secretary => $translator->translate('Secretary', locale: $language?->getLangParam()),
            self::Treasurer => $translator->translate('Treasurer', locale: $language?->getLangParam()),
            self::Education => $translator->translate('Education Officer', locale: $language?->getLangParam()),
            self::ExternalAffairs => $translator->translate(
                'External Affairs Officer',
                locale: $language?->getLangParam(),
            ),
            self::InternalAffairs => $translator->translate(
                'Internal Affairs Officer',
                locale: $language?->getLangParam(),
            ),
            self::LegacyEducation => $translator->translate(
                'LEGACY Education Officer',
                locale: $language?->getLangParam(),
            ),
            self::PrOfficer => $translator->translate('PR Officer', locale: $language?->getLangParam()),
            self::ViceChair => $translator->translate('Vice-Chair', locale: $language?->getLangParam()),
            self::BrandManager => $translator->translate('Brand Manager', locale: $language?->getLangParam()),
            self::CareerdevelopmentExternalAffairs => $translator->translate(
                'Career Development and External Affairs Officer',
                locale: $language?->getLangParam(),
            ),
            self::DigitalInfrastructure => $translator->translate(
                'Digital Infrastructure Officer',
                locale: $language?->getLangParam(),
            ),
            self::Information => $translator->translate('Information Officer', locale: $language?->getLangParam()),
            self::Innovation => $translator->translate('Innovation Officer', locale: $language?->getLangParam()),
            self::Community => $translator->translate(
                'Commissaris Verenigingsontwikkeling',
                locale: $language?->getLangParam(),
            ),
        };
    }

    /**
     * Returns a list of functions (and its translations)
     *
     * @return array<string, string>
     */
    public static function getFunctionsArray(
        Translator $translator,
        bool $includeLegacy = true,
        bool $includeCurrent = true,
    ): array {
        $cases = array_filter(
            self::cases(),
            static function ($case) use ($includeLegacy, $includeCurrent) {
                return (!$case->isLegacy() || $includeLegacy) &&
                    ($case->isLegacy() || $includeCurrent);
            },
        );

        return array_combine(
            array_map(static function ($func) {
                return $func->value;
            }, $cases),
            array_map(static function ($func) use ($translator) {
                return $func->getName($translator);
            }, $cases),
        );
    }

    /**
     * Returns a list of functions (and its translations)
     *
     * @return array<non-empty-string, array{
     *  isLegacy: bool,
     *  translations: non-empty-array<array-key, string>
     * }>
     */
    public static function getMultilangArray(
        Translator $translator,
        bool $includeLegacy = true,
        bool $includeCurrent = true,
    ): array {
        $cases = array_filter(
            self::cases(),
            static function ($case) use ($includeLegacy, $includeCurrent) {
                return (!$case->isLegacy() || $includeLegacy) &&
                    ($case->isLegacy() || $includeCurrent);
            },
        );

        return array_combine(
            array_map(static function ($func) {
                return $func->value;
            }, $cases),
            array_map(static function ($func) use ($translator) {
                return [
                    'translations' => [
                        Languages::English->getLangParam() => $func->getName($translator, Languages::English),
                        Languages::Dutch->getLangParam() => $func->getName($translator, Languages::Dutch),
                    ],
                    'isLegacy' => $func->isLegacy(),
                ];
            }, $cases),
        );
    }
}
