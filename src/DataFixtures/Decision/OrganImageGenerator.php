<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Application\Enums\StorageNamespace;
use App\Service\Application\FileStorage;
use GdImage;
use RuntimeException;

use function abs;
use function count;
use function crc32;
use function imagecolorallocate;
use function imagecopyresampled;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagefontheight;
use function imagefontwidth;
use function imagepng;
use function imagestring;
use function intdiv;
use function max;
use function min;
use function strlen;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Draws the banner and the card image the body fixtures need, so the overviews and the body pages serve real files
 * instead of pointing at paths nobody ever wrote anything to. Generated rather than committed, as the career and photo
 * fixtures do.
 *
 * Each is drawn at the shape it is shown in, since what a body stores is already cropped; only the original is written,
 * and the renditions a page asks for are generated the first time one is requested.
 */
final readonly class OrganImageGenerator
{
    /** The built-in font the abbreviation is drawn in before being scaled up. */
    private const int FONT = 5;

    private const int BANNER_WIDTH = 1600;

    private const int BANNER_HEIGHT = 400;

    private const int LOGO_WIDTH = 800;

    private const int LOGO_HEIGHT = 450;

    /** Background colours, picked per body so two of them are told apart at a glance. */
    private const array PALETTE = [
        [
            21,
            101,
            192,
        ],
        [
            46,
            125,
            50,
        ],
        [
            106,
            27,
            154,
        ],
        [
            191,
            54,
            12,
        ],
        [
            0,
            131,
            143,
        ],
    ];

    public function __construct(private FileStorage $fileStorage)
    {
    }

    /**
     * The banner across the top of a body's page, at the wide shape that suits one.
     */
    public function storeBanner(string $abbr): string
    {
        return $this->draw(
            $abbr,
            self::BANNER_WIDTH,
            self::BANNER_HEIGHT,
        );
    }

    /**
     * The image a body is shown by on an overview.
     */
    public function storeLogo(string $abbr): string
    {
        return $this->draw(
            $abbr,
            self::LOGO_WIDTH,
            self::LOGO_HEIGHT,
        );
    }

    /**
     * @param positive-int $width
     * @param positive-int $height
     */
    private function draw(
        string $abbr,
        int $width,
        int $height,
    ): string {
        $image = imagecreatetruecolor(
            $width,
            $height,
        );
        if (false === $image) {
            throw new RuntimeException('Cannot create a canvas for a fixture image.');
        }

        $background = self::PALETTE[abs(crc32($abbr)) % count(self::PALETTE)];
        imagefilledrectangle(
            $image,
            0,
            0,
            $width,
            $height,
            $this->allocate(
                $image,
                $background,
            ),
        );

        $this->drawCaption(
            $image,
            $abbr,
            $background,
            $width,
            $height,
        );

        return $this->store($image);
    }

    /**
     * Draws the abbreviation across the middle. GD's built-in fonts top out at fifteen pixels tall, which is lost on
     * artwork this size, so the text is drawn small onto a strip of the colour it lands on and copied over enlarged.
     *
     * @param array{int<0, 255>, int<0, 255>, int<0, 255>} $background
     */
    private function drawCaption(
        GdImage $target,
        string $text,
        array $background,
        int $width,
        int $height,
    ): void {
        $textWidth = max(
            1,
            imagefontwidth(self::FONT) * strlen($text),
        );
        $textHeight = max(
            1,
            imagefontheight(self::FONT),
        );
        $scale = max(
            1,
            min(
                intdiv(
                    intdiv(
                        $width * 3,
                        5,
                    ),
                    $textWidth,
                ),
                intdiv(
                    intdiv(
                        $height * 3,
                        5,
                    ),
                    $textHeight,
                ),
            ),
        );

        $strip = imagecreatetruecolor(
            $textWidth,
            $textHeight,
        );
        if (false === $strip) {
            throw new RuntimeException('Cannot create a canvas for a fixture image caption.');
        }

        imagefilledrectangle(
            $strip,
            0,
            0,
            $textWidth,
            $textHeight,
            $this->allocate(
                $strip,
                $background,
            ),
        );
        imagestring(
            $strip,
            self::FONT,
            0,
            0,
            $text,
            $this->allocate(
                $strip,
                [
                    255,
                    255,
                    255,
                ],
            ),
        );

        imagecopyresampled(
            $target,
            $strip,
            intdiv(
                $width - $textWidth * $scale,
                2,
            ),
            intdiv(
                $height - $textHeight * $scale,
                2,
            ),
            0,
            0,
            $textWidth * $scale,
            $textHeight * $scale,
            $textWidth,
            $textHeight,
        );
    }

    private function store(GdImage $image): string
    {
        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'fixture-organ-image-',
        );
        if (false === $temporaryFile) {
            throw new RuntimeException('Could not create a temporary file for a fixture image.');
        }

        imagepng(
            $image,
            $temporaryFile,
        );

        try {
            return $this->fileStorage->store(
                StorageNamespace::OrganImage,
                $temporaryFile,
            )->path;
        } finally {
            unlink($temporaryFile);
        }
    }

    /**
     * @param array{int<0, 255>, int<0, 255>, int<0, 255>} $color
     */
    private function allocate(
        GdImage $image,
        array $color,
    ): int {
        [
            $red,
            $green,
            $blue,
        ] = $color;

        $allocated = imagecolorallocate(
            $image,
            $red,
            $green,
            $blue,
        );
        if (false === $allocated) {
            throw new RuntimeException('Cannot allocate a colour for a fixture image.');
        }

        return $allocated;
    }
}
