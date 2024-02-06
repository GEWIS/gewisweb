<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\NoImage;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExtensionInterface;
use Override;

class NoImageExtension implements ExtensionInterface
{
    #[Override]
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(Image::class, new NoImageRenderer(), 10);
    }
}
