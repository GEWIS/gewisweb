<?php

declare(strict_types=1);

namespace Frontpage\Controller;

use Activity\Service\ActivityQuery as ActivityQueryService;
use Decision\Model\Enums\OrganTypes;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

use function array_merge;

class OrganController extends AbstractActionController
{
    public function __construct(
        private readonly ActivityQueryService $activityQueryService,
        private readonly OrganService $organService,
    ) {
    }

    public function committeeListAction(): ViewModel
    {
        return new ViewModel(
            [
                'committees' => $this->organService->findActiveOrgansByType(OrganTypes::Committee),
            ],
        );
    }

    public function historicalCommitteeListAction(): ViewModel
    {
        return new ViewModel(
            [
                'committees' => $this->organService->findAbrogatedOrgansByType(OrganTypes::Committee),
            ],
        );
    }

    public function fraternityListAction(): ViewModel
    {
        return new ViewModel(
            [
                'activeFraternities' => $this->organService->findActiveOrgansByType(OrganTypes::Fraternity),
                'abrogatedFraternities' => $this->organService->findAbrogatedOrgansByType(OrganTypes::Fraternity),
            ],
        );
    }

    public function organAction(): ViewModel
    {
        $type = OrganTypes::from($this->params()->fromRoute('type'));
        $abbr = $this->params()->fromRoute('abbr');
        $organ = $this->organService->findOrganByAbbr($abbr, $type, true);

        if (null === $organ) {
            return $this->notFoundAction();
        }

        $organMemberInformation = $this->organService->getOrganMemberInformation($organ);
        $activities = $this->activityQueryService->getOrganActivities($organ, 5);

        return new ViewModel(
            array_merge(
                [
                    'organ' => $organ,
                    'activities' => $activities,
                ],
                $organMemberInformation,
            ),
        );
    }
}
