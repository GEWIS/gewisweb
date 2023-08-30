<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Http\PhpEnvironment\Response as EnvironmentResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
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
