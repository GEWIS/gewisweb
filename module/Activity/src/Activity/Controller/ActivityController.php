<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use \Activity\Model\Activity as ActivityModel;
use \Activity\Form\Activity as ActivityForm;


class ActivityController extends AbstractActionController {
    private $modelActivity;
    public function getModels() {
        $sm = $this->getServiceLocator();
        $this->modelActivity = $sm->get('Activity\Model\Activity');
    }

	public function indexAction() {
	}

    public function createAction() {
        $form = new ActivityForm();
        if ($this->getRequest()->isPost()) {
            $activity = new ActivityModel();
            $form->setInputFilter($activity->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $em = $this->serviceLocator->get('Doctrine\ORM\EntityManager');
                $activity->create($form->getData());
                $em->persist($activity);
                $em->flush();

            }
        }
        return ['form' => $form];
    }

}
