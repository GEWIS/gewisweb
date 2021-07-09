<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OrganController extends AbstractActionController
{

    /**
     * @var \Decision\Service\Organ
     */
    private $organService;

    public function __construct(\Decision\Service\Organ $organService)
    {
        $this->organService = $organService;
    }

    /**
     * Index action, shows all active organs.
     */
    public function indexAction()
    {
        return new ViewModel([
            'organs' => $this->organService->getOrgans()
        ]);
    }

    /**
     * Show an organ.
     */
    public function showAction()
    {
        $organId = $this->params()->fromRoute('organ');
        try {
            $organ = $this->organService->getOrgan($organId);
            $organMemberInformation = $this->organService->getOrganMemberInformation($organ);
            return new ViewModel(array_merge([
                'organ' => $organ
            ], $organMemberInformation));
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }
    }
}
