<?php

namespace Decision\Controller;

use Decision\Service\Organ;
use Doctrine\ORM\NoResultException;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class OrganController extends AbstractActionController
{
    /**
     * @var Organ
     */
    private $organService;

    public function __construct(Organ $organService)
    {
        $this->organService = $organService;
    }

    /**
     * Index action, shows all active organs.
     */
    public function indexAction()
    {
        return new ViewModel(
            [
                'organs' => $this->organService->getOrgans(),
            ]
        );
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

            return new ViewModel(
                array_merge(
                    [
                        'organ' => $organ,
                    ],
                    $organMemberInformation
                )
            );
        } catch (NoResultException $e) {
            return $this->notFoundAction();
        }
    }
}
