<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Service\Application\ImageManagerProvider;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Typography\FontFactory;

use function atan2;
use function max;
use function mb_strlen;
use function rad2deg;
use function sqrt;

/**
 * The watermark is burnt into the pixels rather than laid over them as its own object, so it cannot be peeled off the
 * delivered PDF: removing it would mean repainting the page underneath.
 *
 * Both lines are drawn straight onto the page. Compositing a pre-rendered overlay would save the text layout, but
 * Intervention's canvases are opaque, so it would have to be built out of a transparent image by hand for a saving that
 * is small next to decoding and re-encoding the page itself.
 */
final readonly class WatermarkRenderer
{
    /** GEWIS red. Matches the watermark the previous site drew, so a leaked copy from either era looks the same. */
    private const string COLOR = '212, 0, 0';

    private const float DIAGONAL_OPACITY = 0.35;
    private const float FOOTER_OPACITY = 0.75;

    /** The share of the page diagonal the rotated line is drawn across. */
    private const float DIAGONAL_COVERAGE = 0.9;

    /** The share of the page width the footer line is drawn across. */
    private const float FOOTER_COVERAGE = 0.95;

    /** How far down the page the footer line sits. */
    private const float FOOTER_POSITION = 0.94;

    /**
     * Average glyph advance of the watermark face as a fraction of the font size, used to pick a size that fills the
     * intended width. DejaVu Sans Bold is close enough to 0.6 em that a single factor beats measuring every string.
     */
    private const float AVERAGE_GLYPH_WIDTH = 0.6;

    private const int MINIMUM_FONT_SIZE = 6;

    public function __construct(
        private ImageManagerProvider $imageManagerProvider,
        private string $watermarkFontPath,
    ) {
    }

    public function stamp(
        string $pagePath,
        string $text,
        string $targetPath,
        int $quality,
    ): void {
        $page = $this->imageManagerProvider->create()->decodePath($pagePath);

        $width = $page->width();
        $height = $page->height();
        $diagonal = sqrt(($width * $width) + ($height * $height));

        $page->text(
            $text,
            (int) ($width / 2),
            (int) ($height / 2),
            function (FontFactory $font) use ($diagonal, $height, $width, $text): void {
                $font->filename($this->watermarkFontPath);
                $font->size($this->fitFontSize(
                    $diagonal * self::DIAGONAL_COVERAGE,
                    $text,
                ));
                $font->color('rgba(' . self::COLOR . ', ' . self::DIAGONAL_OPACITY . ')');
                $font->align(
                    'center',
                    'center',
                );
                // Run the line corner to corner rather than at a fixed angle, so it spans the page whatever its shape.
                // Negative because the angle turns clockwise, and the line should rise from the bottom left.
                $font->angle(-rad2deg(atan2(
                    $height,
                    $width,
                )));
            },
        );

        $page->text(
            $text,
            (int) ($width / 2),
            (int) ($height * self::FOOTER_POSITION),
            function (FontFactory $font) use ($width, $text): void {
                $font->filename($this->watermarkFontPath);
                $font->size($this->fitFontSize(
                    $width * self::FOOTER_COVERAGE,
                    $text,
                ));
                $font->color('rgba(' . self::COLOR . ', ' . self::FOOTER_OPACITY . ')');
                $font->align(
                    'center',
                    'center',
                );
            },
        );

        $page->encode(new JpegEncoder(quality: $quality, strip: true))->save($targetPath);
    }

    /**
     * The font size at which the text is about as wide as the space it should fill.
     */
    private function fitFontSize(
        float $targetWidth,
        string $text,
    ): int {
        $characters = max(
            1,
            mb_strlen($text),
        );

        return max(
            self::MINIMUM_FONT_SIZE,
            (int) ($targetWidth / ($characters * self::AVERAGE_GLYPH_WIDTH)),
        );
    }
}
