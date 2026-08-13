<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Service\Application\ImageUrlBuilder;
use Override;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * An image on a custom page is served from the site's own image pipeline or it is not served at all.
 *
 * The pipeline is what resizes an image to the variant a page asks for and hands it out with the caching the rest of
 * the site's images get. An image that points anywhere else (a file somewhere on the internet, a data URI pasted into
 * the editor) skips all of that: it is fetched at whatever size it happens to be, from a host that can change or
 * remove it, and the page has no say in either. So the source is kept only when it addresses the pipeline; the editor
 * uploads into it, which is how an image gets there in the first place.
 */
final readonly class PageImageSourceSanitizer implements AttributeSanitizerInterface
{
    public function __construct(
        private ImageUrlBuilder $imageUrlBuilder,
    ) {
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function getSupportedElements(): array
    {
        return ['img'];
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function getSupportedAttributes(): array
    {
        return ['src'];
    }

    #[Override]
    public function sanitizeAttribute(
        string $element,
        string $attribute,
        string $value,
        HtmlSanitizerConfig $config,
    ): ?string {
        return $this->imageUrlBuilder->isPipelineUrl($value)
            ? $value
            : null;
    }
}
