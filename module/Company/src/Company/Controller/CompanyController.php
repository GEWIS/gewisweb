<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CompanyController extends AbstractActionController {
	
	public function indexAction() {
	
	$vm = new ViewModel();
	return $vm;
	
	}

}
