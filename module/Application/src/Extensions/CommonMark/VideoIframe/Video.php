<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe;

class Video
{
    public function __construct(
        private readonly VideoPlatforms $platform,
        private readonly string $identifier,
    ) {
    }

    public function getPlatform(): VideoPlatforms
    {
        return $this->platform;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
