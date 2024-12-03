<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe\Parsers;

use Application\Extensions\CommonMark\VideoIframe\Video;
use Application\Extensions\CommonMark\VideoIframe\VideoPlatforms;
use Application\Extensions\CommonMark\VideoIframe\VideoUrlParserInterface;
use Override;

use function preg_match;

class VimeoUrlParser implements VideoUrlParserInterface
{
    // phpcs:ignore Generic.Files.LineLength.TooLong -- splitting regex is asking for trouble
    private const string REGEX = '/(?:vimeo\.com\/(?:\d+|[^\/]+\/[^\/]+\/video\/|album\/[^\/]+\/video\/|channels\/[^\/]+\/|groups\/[^\/]+\/videos\/|ondemand\/[^\/]+\/)|player\.vimeo\.com\/video\/)(\d+)/';

    #[Override]
    public function parse(string $url): ?Video
    {
        if (preg_match(self::REGEX, $url, $matches)) {
            return new Video(VideoPlatforms::Vimeo, $matches[1]);
        }

        return null;
    }
}
