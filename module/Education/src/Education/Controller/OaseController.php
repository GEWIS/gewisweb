<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Controller that handles communication with OASE.
 *
 * This controller will only be called from console routes.
 */
class OaseController extends AbstractActionController
{
    public function indexAction()
    {
        $console = $this->getServiceLocator()->get('console');

        echo "WARNING: this command will take very long to execute (20 minutes)\n";
        echo "Do you want to continue? [y/N] ";

        if (strtolower($console->readLine(1)) != 'y') {
            return;
        }

        $oaseService = $this->getOaseService();

        $oaseService->update();
    }

    public function studiesAction()
    {
        // show all studies
        $service = $this->getOaseService();

        var_dump($service->getAllStudies());
    }

    public function courseAction()
    {
        // show a course
        $service = $this->getOaseService();

        var_dump($service->getCourse($this->getRequest()->getParam('code')));
    }

    /**
     * Get the OASE service.
     */
    public function getOaseService()
    {
        return $this->getServiceLocator()->get('education_service_oase');
    }
}
