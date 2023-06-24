<?php

declare(strict_types=1);

namespace Photo\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use League\Glide\Urls\UrlBuilder;

/**
 * Url view helper for generating (signed) glide urls
 * Usage: $this->glideUrl()->getUrl('path to image', ['parameters']);.
 */
class GlideUrl extends AbstractHelper
{
    protected UrlBuilder $urlBuilder;

    /**
     * @return GlideUrl
     */
    public function __invoke(): self
    {
        return $this;
    }

    /**
     * Gets a signed glide URL.
     *
     * @param array{w: int, h: int, fm?: string, q?: int} $params
     */
    public function getUrl(
        string $imagePath,
        array $params,
    ): string {
        // If the encoding format is not specifically defined, default to webp.
        if (!isset($params['fm'])) {
            $params['fm'] = 'webp';
        }

        // If the quality is not specifically defined, default to 80 (90 is standard).
        if (!isset($params['q'])) {
            $params['q'] = 80;
        }

        return $this->urlBuilder->getUrl($imagePath, $params);
    }

    /**
     * Set the url builder.
     */
    public function setUrlBuilder(UrlBuilder $urlBuilder): void
    {
        $this->urlBuilder = $urlBuilder;
    }
}
