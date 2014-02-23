<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Controller that handles communication with OASE.
 *
 * This controller will only be called from console routes.
 */
class OaseController extends AbstractActionController {

    public function indexAction()
    {
        echo "Hello, World!\n";
    }
}
