<?php

namespace Frontpage\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\View;

// TODO: I believe this class is not used anywhere. Is it safe to remove?

class AdminController extends AbstractActionController
{
    public function indexAction()
    {
        return new View();
    }
}
