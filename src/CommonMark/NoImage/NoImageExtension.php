<?php

declare(strict_types=1);

namespace App\CommonMark\NoImage;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExtensionInterface;
use Override;

/**
 * Renders Markdown images as their literal source text instead of `<img>` tags, so user-authored content cannot embed
 * arbitrary remote images.
 */
class NoImageExtension implements ExtensionInterface
{
    #[Override]
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(
            Image::class,
            new NoImageRenderer(),
            10,
        );
    }
}
