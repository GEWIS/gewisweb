<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\ImageVariant;

use function implode;
use function preg_match;

/**
 * Builds the serving URL for an image variant, signing it when the namespace is private. Used both by the Twig
 * `image_url`/`image_srcset` helpers and by services that build image URLs in PHP (such as the photo album manifest),
 * so the URL shape and signing live in one place.
 */
final readonly class ImageUrlBuilder
{
    private const string SERVING_PREFIX = '/img/';

    public function __construct(
        private ImageSigner $imageSigner,
        private FilePathResolver $pathResolver,
    ) {
    }

    /**
     * Whether a URL addresses this pipeline: the shape {@see self::url()} produces, with at least something after the
     * variant. The custom-page sanitizer keeps an image only when this says so.
     */
    public function isPipelineUrl(string $url): bool
    {
        if (
            1 !== preg_match(
                '#\A' . self::SERVING_PREFIX . '([^/]+)/[^?\#]+#',
                $url,
                $matches,
            )
        ) {
            return false;
        }

        // The serving route is read exactly as it is written, so a variant in the wrong casing or one the pipeline
        // does not have addresses nothing: keeping such a URL would leave a picture that can only ever be broken.
        return null !== ImageVariant::tryFrom($matches[1]);
    }

    /**
     * The serving URL for a variant of a stored source path. Private (members-only) namespaces get a day-signature;
     * public ones are left bare. The URL is built directly (the /img route is non-localised and mounted at the site
     * root) so the slash-bearing path stays literal.
     */
    public function url(
        string $path,
        ImageVariant $variant,
    ): string {
        $url = self::SERVING_PREFIX . $variant->value . '/' . $path;

        $namespace = $this->pathResolver->namespaceForPath($path);
        if (
            null !== $namespace
            && $namespace->isPrivate()
        ) {
            [
                $expires, $signature
            ] = $this->imageSigner->sign(
                $variant->value,
                $path,
            );
            $url .= '?expires=' . $expires . '&signature=' . $signature;
        }

        return $url;
    }

    /**
     * A responsive `srcset` string for the given variants of a source path, each entry being the variant URL followed
     * by its width descriptor.
     *
     * @param list<ImageVariant> $variants
     */
    public function srcset(
        string $path,
        array $variants,
    ): string {
        $entries = [];
        foreach ($variants as $variant) {
            $entries[] = $this->url(
                $path,
                $variant,
            ) . ' ' . $variant->width() . 'w';
        }

        return implode(
            ', ',
            $entries,
        );
    }
}
