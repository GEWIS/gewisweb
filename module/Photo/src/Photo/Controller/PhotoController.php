<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PhotoController extends AbstractActionController {
	
	public function indexAction() {
	
	$vm = new ViewModel();
	return $vm;
	
	}

}
