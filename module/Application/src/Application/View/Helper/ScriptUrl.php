<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Exception;

/**
 * Url view helper for use inside javascript code.
 * Usage: $this->scriptUrl()->requireUrl('/url/route');
 *
 * @package Application\View\Helper
 */
class ScriptUrl extends AbstractHelper
{
    /**
     * @return \Application\View\Helper\ScriptUrl
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Makes an url route available to the javascript url helper.
     *
     * @param string $name Name of the route.
     * @param array $params of the route to make available.
     */
    public function requireUrl($name, $params = array())
    {
        $scriptParams = array();

        foreach($params as $param) {
            $scriptParams[$param] = '{' . $param . '}';
        }

        $url = $this->getView()->url($name, $scriptParams);
        // Include data as inline script
        $this->getView()->inlineScript()->captureStart();
        echo 'URLHelper.addUrl("'. $name . '", "'. urldecode($url) . '");';
        $this->getView()->inlineScript()->captureEnd();
    }
}
