<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Controller that handles communication with OASE.
 *
 * This controller will only be called from console routes.
 */
class OaseController extends AbstractActionController {

    public function indexAction()
    {
        $oaseService = $this->getOaseService();

        $oaseService->update();
    }

    /**
     * Get the OASE service.
     */
    public function getOaseService()
    {
        return $this->getServiceLocator()->get('education_service_oase');
    }
}
