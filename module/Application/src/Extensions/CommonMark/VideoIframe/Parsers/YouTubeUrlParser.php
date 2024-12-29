<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe\Parsers;

use Application\Extensions\CommonMark\VideoIframe\Video;
use Application\Extensions\CommonMark\VideoIframe\VideoPlatforms;
use Application\Extensions\CommonMark\VideoIframe\VideoUrlParserInterface;
use Override;

use function preg_match;

class YouTubeUrlParser implements VideoUrlParserInterface
{
    // phpcs:ignore Generic.Files.LineLength.TooLong -- splitting regex is asking for trouble
    private const string REGEX = '/(?:m\.)?(?:youtube\.com\/(?:watch\?v=|v\/|embed\/)|youtu\.be\/|youtube-nocookie\.com\/embed\/)([\w-]+)/';

    #[Override]
    public function parse(string $url): ?Video
    {
        if (preg_match(self::REGEX, $url, $matches)) {
            return new Video(VideoPlatforms::YouTube, $matches[1]);
        }

        return null;
    }
}
