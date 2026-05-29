<?php

declare(strict_types=1);

namespace App\CommonMark\NoImage;

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;
use Override;

use function sprintf;

class NoImageRenderer implements NodeRendererInterface
{
    #[Override]
    public function render(
        Node $node,
        ChildNodeRendererInterface $childRenderer,
    ): string {
        Image::assertInstanceOf($node);

        // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        /** @var Image $node */

        // Escape the title and URL: this renderer writes them straight into the HTML output, so unescaped
        // metacharacters would otherwise allow HTML injection (the view page renders the full, un-stripped result).
        return sprintf(
            '![%s](%s)',
            Xml::escape($node->getTitle() ?? ''),
            Xml::escape($node->getUrl()),
        );
    }
}
