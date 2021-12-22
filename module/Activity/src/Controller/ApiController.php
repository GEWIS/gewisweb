<?php

namespace Activity\Controller;

use Activity\Service\{
    AclService,
    ActivityQuery as ActivityQueryService,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use User\Permissions\NotAllowedException;

class ApiController extends AbstractActionController
{
    /**
     * @var ActivityQueryService
     */
    private ActivityQueryService $activityQueryService;

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * ApiController constructor.
     *
     * @param ActivityQueryService $activityQueryService
     * @param AclService $aclService
     */
    public function __construct(
        ActivityQueryService $activityQueryService,
        AclService $aclService,
    ) {
        $this->activityQueryService = $activityQueryService;
        $this->aclService = $aclService;
    }

    /**
     * List all activities.
     */
    public function listAction()
    {
        if (!$this->aclService->isAllowed('list', 'activityApi')) {
            $translator = $this->activityQueryService->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to access the activities through the API')
            );
        }

        $activities = $this->activityQueryService->getUpcomingActivities();
        $activitiesArray = [];

        foreach ($activities as $activity) {
            $activitiesArray[] = $activity->toArray();
        }

        return new JsonModel($activitiesArray);
    }
}
