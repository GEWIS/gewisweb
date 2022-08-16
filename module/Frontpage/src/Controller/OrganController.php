<?php

namespace Frontpage\Controller;

use Activity\Service\ActivityQuery as ActivityQueryService;
use Decision\Model\Enums\OrganTypes;
use Decision\Model\Organ;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class OrganController extends AbstractActionController
{
    public function __construct(
        private readonly ActivityQueryService $activityQueryService,
        private readonly OrganService $organService,
    ) {
    }

    public function committeeListAction(): ViewModel
    {
        $committees = $this->organService->findActiveOrgansByType(OrganTypes::Committee);

        return new ViewModel(
            [
                'committees' => $committees,
            ]
        );
    }

    public function fraternityListAction(): ViewModel
    {
        $activeFraternities = $this->organService->findActiveOrgansByType(OrganTypes::Fraternity);
        $abrogatedFraternities = $this->organService->findAbrogatedOrgansByType(OrganTypes::Fraternity);

        return new ViewModel(
            [
                'activeFraternities' => $activeFraternities,
                'abrogatedFraternities' => $abrogatedFraternities,
            ]
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
        $activities = $this->activityQueryService->getOrganActivities($organ, 3);

        return new ViewModel(
            array_merge(
                [
                    'organ' => $organ,
                    'activities' => $activities,
                ],
                $organMemberInformation
            )
        );
    }
}
