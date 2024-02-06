<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe;

enum VideoPlatforms: string
{
    case Vimeo = 'vimeo';
    case YouTube = 'youtube';

    public function getUrl(string $identifier): string
    {
        return match ($this) {
            self::Vimeo => 'https://player.vimeo.com/video/' . $identifier,
            self::YouTube => 'https://www.youtube-nocookie.com/embed/' . $identifier,
        };
    }
}
