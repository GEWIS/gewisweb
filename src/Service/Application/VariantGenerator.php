<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use Intervention\Image\Encoders\WebpEncoder;

use function bin2hex;
use function random_bytes;
use function sprintf;
use function strrpos;
use function substr;

/**
 * Generates the pre-rendered WebP variants of a stored image into the disposable variant cache
 * (`cache/images/{variant}/<mirror of source path>.webp`). Because source paths are content-addressed, a variant is
 * immutable once written, so the cache is safe to regenerate at any time and never needs backing up.
 *
 * Every variant is EXIF-oriented and has its metadata stripped (GPS never leaks through a variant). Width-fit variants
 * preserve aspect ratio and are never upscaled. If the original is narrower than the target the variant is skipped
 * (and the manifest reports the real dimensions).
 */
final readonly class VariantGenerator
{
    public function __construct(
        private FileStorage $fileStorage,
        private ImageManagerProvider $imageManagerProvider,
    ) {
    }

    /**
     * Generate every variant of the given source for a consumer profile.
     */
    public function generate(
        string $sourcePath,
        ImageProfile $profile,
    ): void {
        foreach ($profile->variants() as $variant) {
            $this->generateVariant(
                $sourcePath,
                $variant,
                $profile->webpQuality(),
            );
        }
    }

    /**
     * Generate a single variant of the given source at the given WebP quality. Returns whether a variant file now
     * exists for it: false when the source is missing or (during pre-generation, `$skipUpscale` true) the width-fit
     * variant was skipped because the original is narrower than the target.
     *
     * Pre-generation skips upscales so no redundant larger-than-original variant is stored; the synchronous
     * generate-on-miss path passes `$skipUpscale = false`, capping at the original width (`scaleDown` never upscales)
     * so a valid original always yields something to serve rather than a 404.
     */
    public function generateVariant(
        string $sourcePath,
        ImageVariant $variant,
        int $quality,
        bool $skipUpscale = true,
    ): bool {
        if (!$this->fileStorage->exists($sourcePath)) {
            return false;
        }

        $cachePath = $this->cachePath(
            $sourcePath,
            $variant,
        );
        if ($this->fileStorage->exists($cachePath)) {
            // Content-addressed source means an existing variant is already correct.
            return true;
        }

        $image = $this->imageManagerProvider->create()
            ->decodeBinary($this->fileStorage->read($sourcePath))
            ->orient();

        if ($variant->isCrop()) {
            $image->cover(
                $variant->width(),
                (int) $variant->height(),
            );
        } else {
            if (
                $skipUpscale
                && $image->width() < $variant->width()
            ) {
                // Never store an upscaled width-fit variant during pre-generation.
                return false;
            }

            // `scaleDown` never upscales, so this caps at the original width when the target is larger.
            $image->scaleDown(width: $variant->width());
        }

        $encoded = $image->encode(new WebpEncoder(quality: $quality, strip: true));
        $this->writeAtomically(
            $cachePath,
            $encoded->toString(),
        );

        return true;
    }

    /**
     * Whether a generated variant already exists for the given source.
     */
    public function variantExists(
        string $sourcePath,
        ImageVariant $variant,
    ): bool {
        return $this->fileStorage->exists($this->cachePath($sourcePath, $variant));
    }

    /**
     * The cache path a variant of the given source is stored at, i.e.
     * `cache/images/{variant}/<source without extension>.webp`.
     */
    public function cachePath(
        string $sourcePath,
        ImageVariant $variant,
    ): string {
        return sprintf(
            'cache/images/%s/%s.webp',
            $variant->value,
            $this->withoutExtension($sourcePath),
        );
    }

    /**
     * Write to the final path via a unique temporary file and a move, so a partially written variant is never visible
     * and concurrent generations of the (identical, immutable) content cannot corrupt it.
     */
    private function writeAtomically(
        string $path,
        string $contents,
    ): void {
        $temporaryPath = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $this->fileStorage->write(
            $temporaryPath,
            $contents,
        );
        $this->fileStorage->move(
            $temporaryPath,
            $path,
        );
    }

    private function withoutExtension(string $path): string
    {
        $dot = strrpos(
            $path,
            '.',
        );
        $slash = strrpos(
            $path,
            '/',
        );

        if (
            false === $dot
            || (
                false !== $slash
                && $dot < $slash
            )
        ) {
            return $path;
        }

        return substr(
            $path,
            0,
            $dot,
        );
    }
}
