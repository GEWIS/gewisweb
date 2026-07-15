<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\ImageVariant;

use function implode;

/**
 * Builds the serving URL for an image variant, signing it when the namespace is private. Used both by the Twig
 * `image_url`/`image_srcset` helpers and by services that build image URLs in PHP (such as the photo album manifest),
 * so the URL shape and signing live in one place.
 */
final readonly class ImageUrlBuilder
{
    public function __construct(
        private ImageSigner $imageSigner,
        private ImagePathResolver $pathResolver,
    ) {
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
        $url = '/img/' . $variant->value . '/' . $path;

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
