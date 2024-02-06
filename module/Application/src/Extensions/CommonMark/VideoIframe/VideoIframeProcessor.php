<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;

class VideoIframeProcessor
{
    /**
     * @param VideoUrlParserInterface[] $parsers
     */
    public function __construct(private readonly array $parsers)
    {
    }

    public function __invoke(DocumentParsedEvent $event): void
    {
        $walker = $event->getDocument()->walker();

        while ($item = $walker->next()) {
            $link = $item->getNode();

            if (!($link instanceof Link)) {
                continue;
            }

            foreach ($this->parsers as $parser) {
                $video = $parser->parse($link->getUrl());

                if (null === $video) {
                    continue;
                }

                $link->replaceWith(new VideoIframe($video));
            }
        }
    }
}
