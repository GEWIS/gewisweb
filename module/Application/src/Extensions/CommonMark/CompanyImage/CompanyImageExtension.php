<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\CompanyImage;

use Application\View\Helper\GlideUrl;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\ImageRenderer;
use League\CommonMark\Extension\ExtensionInterface;
use Override;

class CompanyImageExtension implements ExtensionInterface
{
    public function __construct(private readonly GlideUrl $glideUrl)
    {
    }

    #[Override]
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(
            Image::class,
            new CompanyImageRenderer($environment->getConfiguration(), new ImageRenderer(), $this->glideUrl),
            10,
        );
    }
}
