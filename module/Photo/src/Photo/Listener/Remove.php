<?php

namespace Photo\Listener;

use Zend\ServiceManager\ServiceManager;

/**
 * Doctrine event listener class for Album and Photo entities.
 * Do not instantiate this class manually.
 */

class Remove {
    protected $sm;

    public function __construct(ServiceManager $sm)
    {
        $this->sm = $sm;
    }
}