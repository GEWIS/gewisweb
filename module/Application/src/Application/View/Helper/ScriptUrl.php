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
    public function __invoke()
    {
        return $this;
    }

    public function requireUrl($name, $params)
    {
        $scriptParams = array();

        foreach($params as $param) {
            $scriptParams[$param] = '{' . $param . '}';
        }

        $url = $this->getView()->url($name, $scriptParams);
        // Include data as inline script
        $this->getView()->inlineScript()->captureStart();
        echo 'URLHelper.addUrl("'. urldecode($url) . '");';
        $this->getView()->inlineScript()->captureEnd();
    }
}
