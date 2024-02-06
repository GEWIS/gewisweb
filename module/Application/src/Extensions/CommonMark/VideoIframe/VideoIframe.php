<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe;

use League\CommonMark\Node\Node;

class VideoIframe extends Node
{
    public function __construct(private readonly Video $video)
    {
        parent::__construct();
    }

    public function getVideo(): Video
    {
        return $this->video;
    }
}
