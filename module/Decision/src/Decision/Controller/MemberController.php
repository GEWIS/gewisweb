<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class MemberController extends AbstractActionController
{

    /**
     * Index action, shows all organs.
     */
    public function indexAction()
    {
        return new ViewModel(array(
            'role' => $this->getServiceLocator()->get('user_role')
        ));
    }
}
