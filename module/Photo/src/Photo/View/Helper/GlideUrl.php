<?php

namespace Photo\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use League\Glide\Urls\UrlBuilder;

/**
 * Url view helper for generating (signed) glide url's
 * Usage: $this->scriptUrl()->requireUrl('/url/route');.
 */
class GlideUrl extends AbstractHelper
{
    protected $urlBuilder;

    /**
     * @return GlideUrl
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Gets a signed glide URL.
     *
     * @param $imagePath
     * @param $params
     *
     * @return string
     */
    public function getUrl($imagePath, $params)
    {
        return $this->urlBuilder->getUrl($imagePath, $params);
    }

    /**
     * Set the url builder.
     *
     * @param UrlBuilder $urlBuilder
     */
    public function setUrlBuilder($urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }
}
