<?php

declare(strict_types=1);

namespace App\Entity\User\Enums;

use function strtolower;

/**
 * Semantic device classification persisted on {@see \App\Entity\User\Session}.
 *
 * Values map loosely to Matomo DeviceDetector's `getDeviceName()` output. Some close synonyms
 * (e.g. smartphone / feature phone / phablet) are collapsed into a single case since the only consumer is the icon and
 * a finer split would just add cases that share the same emoji.
 */
enum DeviceTypes: string
{
    case Phone = 'phone';
    case Tablet = 'tablet';
    case Pc = 'pc';
    case Tv = 'tv';
    case Console = 'console';
    case Wearable = 'wearable';
    case Camera = 'camera';
    case Car = 'car';
    case Peripheral = 'peripheral';
    case Media = 'media';
    case Speaker = 'speaker';
    case Bot = 'bot';
    case Unknown = 'unknown';

    /**
     * Map a raw Matomo `DeviceDetector::getDeviceName()` string onto the enum. Unknown / null Matomo names collapse to
     * {@see self::Unknown}.
     */
    public static function fromMatomoName(?string $matomoName): self
    {
        return match (strtolower($matomoName ?? '')) {
            'smartphone', 'feature phone', 'phablet' => self::Phone,
            'tablet' => self::Tablet,
            'desktop' => self::Pc,
            'tv', 'smart display' => self::Tv,
            'console' => self::Console,
            'wearable' => self::Wearable,
            'camera' => self::Camera,
            'car browser' => self::Car,
            'peripheral' => self::Peripheral,
            'portable media player' => self::Media,
            'smart speaker' => self::Speaker,
            default => self::Unknown,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Phone => '📱',
            self::Tablet => '🔳',
            self::Pc => '🖥️',
            self::Tv => '📺',
            self::Console => '🎮',
            self::Wearable => '⌚',
            self::Camera => '📷',
            self::Car => '🚗',
            self::Peripheral => '🖨',
            self::Media => '🎵',
            self::Speaker => '🔊',
            self::Bot => '🤖',
            self::Unknown => '❓',
        };
    }
}
