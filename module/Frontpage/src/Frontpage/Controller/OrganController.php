<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Decision\Model\Organ;
use Zend\View\Model\ViewModel;

class OrganController extends AbstractActionController
{

    /**
     * @var \Frontpage\Service\Frontpage
     */
    private $organService;

    /**
     * @var \Activity\Service\ActivityQuery
     */
    private $activityQueryService;


    public function __construct(\Frontpage\Service\Frontpage $organService, \Activity\Service\ActivityQuery $activityQueryService)
    {
        $this->organService = $organService;
        $this->activityQueryService = $activityQueryService;
    }

    public function committeeListAction()
    {
        $committees = $this->organService->findActiveOrgansByType(Organ::ORGAN_TYPE_COMMITTEE);

        return new ViewModel([
            'committees' => $committees
        ]);
    }

    public function fraternityListAction()
    {
        $activeFraternities = $this->organService->findActiveOrgansByType(Organ::ORGAN_TYPE_FRATERNITY);
        $abrogatedFraternities = $this->organService->findAbrogatedOrgansByType(Organ::ORGAN_TYPE_FRATERNITY);

        return new ViewModel([
            'activeFraternities' => $activeFraternities,
            'abrogatedFraternities' => $abrogatedFraternities
        ]);
    }

    public function organAction()
    {
        $type = $this->params()->fromRoute('type');
        $abbr = $this->params()->fromRoute('abbr');
        $organService = $this->organService;
        try {
            $organ = $organService->findOrganByAbbr($abbr, $type, true);
            $organMemberInformation = $organService->getOrganMemberInformation($organ);

            $activities = $this->activityQueryService->getOrganActivities($organ, 3);

            return new ViewModel(array_merge([
                'organ' => $organ,
                'activities' => $activities
            ], $organMemberInformation));
        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }
    }
}
