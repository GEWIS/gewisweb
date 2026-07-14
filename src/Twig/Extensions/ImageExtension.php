<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Application\Enums\ImageVariant;
use App\Service\Application\ImagePathResolver;
use App\Service\Application\ImageSigner;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function implode;

/**
 * Twig helpers for building image URLs. `image_url(path, variant)` yields the serving URL for a variant of a stored
 * source; `image_srcset(path, variants)` builds a responsive `srcset` string. For private (members-only) namespaces the
 * URL is day-signed (see {@see ImageSigner}) so it caches for the day yet a leaked URL expires; public namespaces are
 * left unsigned.
 *
 * URLs are built directly (the `/img` route is non-localised and mounted at the site root) so that the slash-bearing
 * `{path}` stays literal rather than being percent-encoded by the URL generator.
 */
final class ImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly ImageSigner $imageSigner,
        private readonly ImagePathResolver $pathResolver,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'image_url',
                $this->imageUrl(...),
            ),
            new TwigFunction(
                'image_srcset',
                $this->imageSrcset(...),
            ),
        ];
    }

    /**
     * The serving URL for a variant of a stored source path, signed when the namespace is private.
     */
    public function imageUrl(
        string $path,
        ImageVariant|string $variant,
    ): string {
        $variant = $variant instanceof ImageVariant
            ? $variant
            : ImageVariant::from($variant);

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
     * A responsive `srcset` string for the given variants of a source path: each entry is the variant URL followed by
     * its width descriptor, for example "/img/w320/... 320w, /img/w640/... 640w".
     *
     * @param list<ImageVariant|string> $variants
     */
    public function imageSrcset(
        string $path,
        array $variants,
    ): string {
        $entries = [];
        foreach ($variants as $variant) {
            $variant = $variant instanceof ImageVariant
                ? $variant
                : ImageVariant::from($variant);
            $entries[] = $this->imageUrl(
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
