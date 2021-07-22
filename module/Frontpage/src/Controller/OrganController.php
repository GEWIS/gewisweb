<?php

namespace Frontpage\Controller;

use Activity\Service\ActivityQuery as ActivityQueryService;
use Decision\Model\Organ;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class OrganController extends AbstractActionController
{
    /**
     * @var ActivityQueryService
     */
    private ActivityQueryService $activityQueryService;

    /**
     * @var OrganService
     */
    private OrganService $organService;

    /**
     * OrganController constructor.
     *
     * @param ActivityQueryService $activityQueryService
     * @param OrganService $organService
     */
    public function __construct(
        ActivityQueryService $activityQueryService,
        OrganService $organService
    ) {
        $this->activityQueryService = $activityQueryService;
        $this->organService = $organService;
    }

    public function committeeListAction()
    {
        $committees = $this->organService->findActiveOrgansByType(Organ::ORGAN_TYPE_COMMITTEE);

        return new ViewModel(
            [
                'committees' => $committees,
            ]
        );
    }

    public function fraternityListAction()
    {
        $activeFraternities = $this->organService->findActiveOrgansByType(Organ::ORGAN_TYPE_FRATERNITY);
        $abrogatedFraternities = $this->organService->findAbrogatedOrgansByType(Organ::ORGAN_TYPE_FRATERNITY);

        return new ViewModel(
            [
                'activeFraternities' => $activeFraternities,
                'abrogatedFraternities' => $abrogatedFraternities,
            ]
        );
    }

    public function organAction()
    {
        $type = $this->params()->fromRoute('type');
        $abbr = $this->params()->fromRoute('abbr');
        $organService = $this->organService;
        $organ = $organService->findOrganByAbbr($abbr, $type, true);
        $organMemberInformation = $organService->getOrganMemberInformation($organ);

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
