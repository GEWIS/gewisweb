<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\NoImage;

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

use function sprintf;

class NoImageRenderer implements NodeRendererInterface
{
    public function render(
        Node $node,
        ChildNodeRendererInterface $childRenderer,
    ): string {
        Image::assertInstanceOf($node);

        // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        /** @var Image $node */

        return sprintf(
            '![%s](%s)',
            $node->getTitle() ?? '',
            $node->getUrl(),
        );
    }
}
