<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container as SessionContainer;

class IndexController extends AbstractActionController
{
    /**
     * Action to switch language.
     */
    public function langAction()
    {
        $session = new SessionContainer('lang');
        $session->lang = $this->params()->fromRoute('lang');

        if ('en' != $session->lang && 'nl' != $session->lang) {
            $session->lang = 'nl';
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->redirect()->toUrl($_SERVER['HTTP_REFERER']);
        }

        return $this->redirect()->toRoute('home');
    }

    /**
     * Action called when loading pages from external templates.
     */
    public function externalAction()
    {
    }

    /**
     * Throws a teapot error.
     */
    public function teapotAction()
    {
        $this->getResponse()->setStatusCode(418);
    }
}
