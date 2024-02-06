<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\VideoIframe;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use Override;
use Stringable;

class VideoIframeRenderer implements NodeRendererInterface
{
    #[Override]
    public function render(
        Node $node,
        ChildNodeRendererInterface $childRenderer,
    ): Stringable {
        VideoIframe::assertInstanceOf($node);

        // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        /** @var VideoIframe $node */
        $video = $node->getVideo();

        return new HtmlElement(
            'div',
            ['style' => 'position: relative; height: 0; padding-bottom: 56.2493%;'],
            new HtmlElement('iframe', [
                'allowfullscreen' => '1',
                'src' => $video->getPlatform()->getUrl($video->getIdentifier()),
                'style' => 'position: absolute; width: 100%; height: 100%; top: 0; left: 0; border: 0;',
            ]),
        );
    }
}
