<?php

namespace Decision\Controller;

use Doctrine\DBAL\Schema\View;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class companyaccountController extends AbstractActionController
{
    public function IndexAction()
    {
        if (!$this->getCompanyService()->isAllowed('view')) {
            $translator = $this->getCompanyService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view this page')
            );
        }
        return new ViewModel();
    }



    /**
    * Get the company service.
    *
    * @return Decision\Service\CompanyAccount
    */
    public function getCompanyService()
    {
        return $this->getServiceLocator()->get('decision_service_companyaccount');
    }
}
