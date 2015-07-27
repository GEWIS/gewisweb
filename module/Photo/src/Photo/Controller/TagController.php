<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class TagController extends AbstractActionController
{
    public function addAction() {
        return new JsonModel(array(
            
        ));
    }

    public function removeAction() {
        return new JsonModel(array(

        ));
    }
}