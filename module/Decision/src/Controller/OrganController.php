<?php

declare(strict_types=1);

namespace Decision\Controller;

use Decision\Service\Organ as OrganService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Override;

use function array_merge;

class OrganController extends AbstractActionController
{
    public function __construct(private readonly OrganService $organService)
    {
    }

    /**
     * Index action, shows all active organs.
     */
    #[Override]
    public function indexAction(): ViewModel
    {
        return new ViewModel(
            [
                'historical' => false,
                'organs' => $this->organService->getOrgans(),
            ],
        );
    }

    public function historyAction(): ViewModel
    {
        return new ViewModel(
            [
                'historical' => true,
                'organs' => $this->organService->getOrgans(true),
            ],
        );
    }

    /**
     * Show an organ.
     */
    public function viewAction(): ViewModel
    {
        $organId = (int) $this->params()->fromRoute('organ');
        $organ = $this->organService->getOrgan($organId);

        if (null === $organ) {
            return $this->notFoundAction();
        }

        $organMemberInformation = $this->organService->getOrganMemberInformation($organ);

        return new ViewModel(
            array_merge(
                [
                    'organ' => $organ,
                ],
                $organMemberInformation,
            ),
        );
    }
}
