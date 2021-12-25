<?php

namespace Photo\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use League\Glide\Urls\UrlBuilder;

/**
 * Url view helper for generating (signed) glide url's
 * Usage: $this->glideUrl()->getUrl('path to image', ['parameters']);.
 */
class GlideUrl extends AbstractHelper
{
    /**
     * @var UrlBuilder
     */
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
     * @param string $imagePath
     * @param array $params
     *
     * @return string
     */
    public function getUrl(
        string $imagePath,
        array $params,
    ): string {
        return $this->urlBuilder->getUrl($imagePath, $params);
    }

    /**
     * Set the url builder.
     *
     * @param UrlBuilder $urlBuilder
     */
    public function setUrlBuilder(UrlBuilder $urlBuilder): void
    {
        $this->urlBuilder = $urlBuilder;
    }
}
