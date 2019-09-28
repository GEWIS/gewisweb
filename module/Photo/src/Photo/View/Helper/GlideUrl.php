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
     * Set the service locator
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }
}
