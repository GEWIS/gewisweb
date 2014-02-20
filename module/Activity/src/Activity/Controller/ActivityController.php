<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ActivityController extends AbstractActionController {
	
	public function indexAction() {
	
	$vm = new ViewModel();
	return $vm;
	
	}

}
