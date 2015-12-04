<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class PollAdminController extends AbstractActionController
{
    public function listAction()
    {
        $adapter = $this->getPollService()->getPaginatorAdapter();
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(15);

        $page = $this->params()->fromRoute('page');

        if ($page) {
            $paginator->setCurrentPageNumber($page);
        }

        $unapprovedPolls = $this->getPollService()->getUnapprovedPolls();
        $session = new SessionContainer('lang');

        return new ViewModel([
            'unapprovedPolls' => $unapprovedPolls,
            'paginator' => $paginator,
            'lang' => $session->lang
        ]);
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
