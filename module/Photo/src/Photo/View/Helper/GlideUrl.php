<?php

namespace Photo\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Exception;
use League\Glide\Urls\UrlBuilderFactory;

/**
 * Url view helper for generating (signed) glide url's
 * Usage: $this->scriptUrl()->requireUrl('/url/route');
 *
 * @package Application\View\Helper
 */
class GlideUrl extends AbstractHelper
{

    protected $urlBuilder;

    /**
     * @return \Photo\View\Helper\GlideUrl
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Gets a signed glide URL
     * @param $imagePath
     * @param $params
     * @return string
     */
    public function getUrl($imagePath, $params)
    {
        return $this->urlBuilder->getUrl($imagePath, $params);
    }

    /**
     * Set the url builder
     *
     * @param \League\Glide\Urls\UrlBuilder $urlBuilder
     */
    public function setUrlBuilder($urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }
}
