<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;

class PollController extends AbstractActionController
{
    public function indexAction()
    {
        $poll = $this->getPollService()->getNewestPoll();
        $details = $this->getPollService()->getPollDetails($poll);

        $session = new SessionContainer('lang');

        return new ViewModel(array_merge($details, array(
            'poll' => $poll,
            'lang' => $session->lang
        )));
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
