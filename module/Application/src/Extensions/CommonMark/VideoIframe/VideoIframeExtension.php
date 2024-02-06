<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe;

use Application\Extensions\CommonMark\VideoIframe\Parsers\VimeoUrlParser;
use Application\Extensions\CommonMark\VideoIframe\Parsers\YouTubeUrlParser;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use Override;

class VideoIframeExtension implements ExtensionInterface
{
    #[Override]
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            new VideoIframeProcessor([
                new VimeoUrlParser(),
                new YouTubeUrlParser(),
            ]),
        )
            ->addRenderer(VideoIframe::class, new VideoIframeRenderer(), 10);
    }
}
