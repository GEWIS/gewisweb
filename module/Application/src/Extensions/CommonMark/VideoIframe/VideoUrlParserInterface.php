<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe;

interface VideoUrlParserInterface
{
    public function parse(string $url): ?Video;
}
