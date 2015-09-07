<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PollController extends AbstractActionController
{
    public function indexAction()
    {

    }

    /**
     * Get the poll service.
     *
     * @return \Frontpage\Service\Poll
     */
    protected function getPollService()
    {
        return $this->getServiceLocator()->get('frontpage_service_poll');
    }
}
