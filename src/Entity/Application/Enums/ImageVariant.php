<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

/**
 * A pre-generated rendition of a source image. Width-fit variants preserve aspect ratio and are never upscaled (a
 * narrower original skips them); fixed-crop variants are cropped to fill an exact box. Every variant is encoded
 * as WebP with metadata stripped. The backing value is the URL segment used when serving (`/img/{variant}/{path}`).
 */
enum ImageVariant: string
{
    // Width-fit ladder (aspect preserved, never upscaled).
    case W320 = 'w320';
    case W640 = 'w640';
    case W960 = 'w960';
    case W1280 = 'w1280';
    case W1920 = 'w1920';
    case W2560 = 'w2560';

    // Fixed-crop renditions.
    case Cover = 'cover';
    case Cover2x = 'cover2x';
    case Square = 'square';
    case Square2x = 'square2x';

    /**
     * The target width, in pixels.
     */
    public function width(): int
    {
        return match ($this) {
            self::W320 => 320,
            self::W640, self::Cover => 640,
            self::W960 => 960,
            self::W1280, self::Cover2x => 1280,
            self::W1920 => 1920,
            self::W2560 => 2560,
            self::Square => 400,
            self::Square2x => 800,
        };
    }

    /**
     * The target height for a fixed-crop variant, or null for a width-fit variant (whose height follows the aspect
     * ratio).
     */
    public function height(): ?int
    {
        return match ($this) {
            self::Cover => 360,
            self::Cover2x => 720,
            self::Square => 400,
            self::Square2x => 800,
            default => null,
        };
    }

    /**
     * Whether this variant is cropped to an exact box (as opposed to fitted to a width).
     */
    public function isCrop(): bool
    {
        return null !== $this->height();
    }
}
