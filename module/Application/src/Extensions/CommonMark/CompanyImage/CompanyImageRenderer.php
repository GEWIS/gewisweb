<?php

declare(strict_types=1);

namespace Application\Extensions\CommonMark\CompanyImage;

use Application\View\Helper\GlideUrl;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\ImageRenderer;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\Config\ConfigurationInterface;
use Stringable;

use function str_starts_with;
use function substr;

class CompanyImageRenderer implements NodeRendererInterface
{
    public function __construct(
        private readonly ConfigurationInterface $config,
        private readonly ImageRenderer $baseImageRenderer,
        private readonly GlideUrl $glideUrl,
    ) {
    }

    public function render(
        Node $node,
        ChildNodeRendererInterface $childRenderer,
    ): Stringable|string {
        Image::assertInstanceOf($node);

        // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        /** @var Image $node */

        // Check if we have a valid image, only accept images that were uploaded specifically for companies.
        if (!str_starts_with($node->getUrl(), '/data/company/')) {
            return '';
        }

        $this->baseImageRenderer->setConfiguration($this->config);
        /** @var HtmlElement $imageElement */
        $imageElement = $this->baseImageRenderer->render($node, $childRenderer);

        $imageElement->setAttribute('style', 'max-width: 100%');
        $imageElement->setAttribute('src', $this->glideUrl->getUrl(substr($node->getUrl(), 5), []));

        return $imageElement;
    }
}
