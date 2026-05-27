<?php

declare(strict_types=1);

namespace App\Entity\Decision\Enums;

use App\Entity\Application\Enums\Languages;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    case DigitalInnovation = 'Commissaris Digitale Innovatie';

    public function isLegacy(): bool
    {
        return !in_array(
            $this,
            [
                self::Chair,
                self::Secretary,
                self::Treasurer,
                self::Education,
                self::ExternalAffairs,
                self::InternalAffairs,
                self::DigitalInnovation,
            ],
            true,
        );
    }

    /**
     * Give the function name with the given translation. If no translator is given, we return the default language.
     */
    public function getName(
        TranslatorInterface $translator,
        ?Languages $language = null,
    ): string {
        return match ($this) {
            self::Chair => $translator->trans(
                'Chair',
                locale: $language?->getLangParam(),
            ),
            self::Secretary => $translator->trans(
                'Secretary',
                locale: $language?->getLangParam(),
            ),
            self::Treasurer => $translator->trans(
                'Treasurer',
                locale: $language?->getLangParam(),
            ),
            self::Education => $translator->trans(
                'Education Officer',
                locale: $language?->getLangParam(),
            ),
            self::ExternalAffairs => $translator->trans(
                'External Affairs Officer',
                locale: $language?->getLangParam(),
            ),
            self::InternalAffairs => $translator->trans(
                'Internal Affairs Officer',
                locale: $language?->getLangParam(),
            ),
            self::LegacyEducation => $translator->trans(
                'LEGACY Education Officer',
                locale: $language?->getLangParam(),
            ),
            self::PrOfficer => $translator->trans(
                'PR Officer',
                locale: $language?->getLangParam(),
            ),
            self::ViceChair => $translator->trans(
                'Vice-Chair',
                locale: $language?->getLangParam(),
            ),
            self::BrandManager => $translator->trans(
                'Brand Manager',
                locale: $language?->getLangParam(),
            ),
            self::CareerdevelopmentExternalAffairs => $translator->trans(
                'Career Development and External Affairs Officer',
                locale: $language?->getLangParam(),
            ),
            self::DigitalInfrastructure => $translator->trans(
                'Digital Infrastructure Officer',
                locale: $language?->getLangParam(),
            ),
            self::Information => $translator->trans(
                'Information Officer',
                locale: $language?->getLangParam(),
            ),
            self::Innovation => $translator->trans(
                'Innovation Officer',
                locale: $language?->getLangParam(),
            ),
            self::Community => $translator->trans(
                'Commissaris Verenigingsontwikkeling',
                locale: $language?->getLangParam(),
            ),
            self::DigitalInnovation => $translator->trans(
                'Commissaris Digitale Innovatie',
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
        TranslatorInterface $translator,
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
            array_map(
                static function ($func) {
                    return $func->value;
                },
                $cases,
            ),
            array_map(
                static function ($func) use ($translator) {
                    return $func->getName($translator);
                },
                $cases,
            ),
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
        TranslatorInterface $translator,
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
            array_map(
                static function ($func) {
                    return $func->value;
                },
                $cases,
            ),
            array_map(
                static function ($func) use ($translator) {
                    return [
                        'translations' => [
                            Languages::English->getLangParam() => $func->getName(
                                $translator,
                                Languages::English,
                            ),
                            Languages::Dutch->getLangParam() => $func->getName(
                                $translator,
                                Languages::Dutch,
                            ),
                        ],
                        'isLegacy' => $func->isLegacy(),
                    ];
                },
                $cases,
            ),
        );
    }
}
