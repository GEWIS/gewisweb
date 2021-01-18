<?php

namespace Frontpage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Decision\Model\Organ;
use Zend\View\Model\ViewModel;

class OrganController extends AbstractActionController
{
    public function committeeListAction()
    {
        $committees = $this->getOrganService()->findActiveOrgansByType(Organ::ORGAN_TYPE_COMMITTEE);
        $vm = new ViewModel([
            'committees' => $committees
        ]);

        return $vm;
    }

    public function fraternityListAction()
    {
        $activeFraternities = $this->getOrganService()->findActiveOrgansByType(Organ::ORGAN_TYPE_FRATERNITY);
        $abrogatedFraternities = $this->getOrganService()->findAbrogatedOrgansByType(Organ::ORGAN_TYPE_FRATERNITY);
        $vm = new ViewModel([
            'activeFraternities' => $activeFraternities,
            'abrogatedFraternities' => $abrogatedFraternities
        ]);

        return $vm;
    }

    public function organAction()
    {
        $type = $this->params()->fromRoute('type');
        $abbr = $this->params()->fromRoute('abbr');
        $organService = $this->getOrganService();
        try {
            $organ = $organService->findOrganByAbbr($abbr, $type, true);
            $organMemberInformation = $organService->getOrganMemberInformation($organ);

            $activities = $this->getActivityQueryService()->getOrganActivities($organ, 3);

            return new ViewModel(array_merge([
                'organ' => $organ,
                'activities' => $activities
            ], $organMemberInformation));

        } catch (\Doctrine\ORM\NoResultException $e) {
            return $this->notFoundAction();
        }
    }

    /**
     * Get the organ service.
     */
    public function getOrganService()
    {
        return $this->getServiceLocator()->get('decision_service_organ');
    }

    /**
     * Get the activity service.
     */
    public function getActivityQueryService()
    {
        return $this->getServiceLocator()->get('activity_service_activityQuery');
    }
}
