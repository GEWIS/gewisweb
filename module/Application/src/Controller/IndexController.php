<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\Http\{
    PhpEnvironment\Response as EnvironmentResponse,
    Response,
};
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

        $url = $this->params()->fromRoute('href');
        if (null !== $url) {
            return $this->redirect()->toUrl('/' . $url);
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
    public function teapotAction(): ViewModel
    {
        /** @var EnvironmentResponse $response */
        $response = $this->getResponse();
        $response->setStatusCode(418);

        return new ViewModel();
    }
}
