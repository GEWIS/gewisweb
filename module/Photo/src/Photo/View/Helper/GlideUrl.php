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

    protected $config;

    /**
     * @return \Photo\View\Helper\GlideUrl
     */
    public function __invoke()
    {
        if ($this->config === null)
            throw new \Exception('No config provided to GlideUrl helper');
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
        $urlBuilder = UrlBuilderFactory::create($this->config['glide']['base_url'], $this->config['glide']['signing_key']);
        return $urlBuilder->getUrl($imagePath, $params);
    }

    /**
     * Gets an URL returning a resized large version of the image
     *
     * @param \Photo\Model\Photo $image
     *
     * @return string
     */
    public function getLargeUrl($image)
    {
        $width = $this->config['photo']['large_thumb_size']['width'];
        $size = [
            'w' => $width,
            'h' => round($width * $image->getAspectRatio())
        ];
        return $this->getUrl($image->getPath(), $size);

    }

    /**
     * Gets an URL returning a resized small version of the image
     *
     * @param \Photo\Model\Photo $image
     *
     * @return string
     */
    public function getThumbnailUrl($image)
    {
        $width = $this->config['photo']['small_thumb_size']['width'];
        $size = [
            'w' => $width,
            'h' => round($width * $image->getAspectRatio())
        ];
        return $this->getUrl($image->getPath(), $size);
    }

    /**
     * Set the service locator
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }
}
