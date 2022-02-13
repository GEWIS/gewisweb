<?php

namespace Application\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container as SessionContainer;

class IndexController extends AbstractActionController
{
    /**
     * Action to switch language.
     */
    public function langAction(): Response
    {
        $session = new SessionContainer('lang');
        $session->lang = $this->params()->fromRoute('lang');

        if ('en' != $session->lang && 'nl' != $session->lang) {
            $session->lang = 'nl';
        }

        $url = $this->getRequest()->getQuery('href');
        if (null !== $url) {
            return $this->redirect()->toUrl($url);
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->redirect()->toUrl($_SERVER['HTTP_REFERER']);
        }

        return $this->redirect()->toRoute('home');
    }

    /**
     * Action called when loading pages from external templates.
     */
    public function externalAction(): void
    {
    }

    /**
     * Throws a teapot error.
     */
    public function teapotAction(): void
    {
        $this->getResponse()->setStatusCode(418);
    }
}
