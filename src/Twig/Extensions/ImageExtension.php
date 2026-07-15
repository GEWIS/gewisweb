<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Application\Enums\ImageVariant;
use App\Service\Application\ImageUrlBuilder;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function array_map;

/**
 * Twig helpers for building image URLs. `image_url(path, variant)` yields the serving URL for a variant of a stored
 * source; `image_srcset(path, variants)` builds a responsive `srcset`. Both accept a variant as an `ImageVariant` or
 * its string value and delegate to {@see ImageUrlBuilder}, which does the signing.
 */
final class ImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly ImageUrlBuilder $urlBuilder,
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

    public function imageUrl(
        string $path,
        ImageVariant|string $variant,
    ): string {
        return $this->urlBuilder->url(
            $path,
            $this->toVariant($variant),
        );
    }

    /**
     * @param list<ImageVariant|string> $variants
     */
    public function imageSrcset(
        string $path,
        array $variants,
    ): string {
        return $this->urlBuilder->srcset(
            $path,
            array_map(
                $this->toVariant(...),
                $variants,
            ),
        );
    }

    private function toVariant(ImageVariant|string $variant): ImageVariant
    {
        return $variant instanceof ImageVariant
            ? $variant
            : ImageVariant::from($variant);
    }
}
