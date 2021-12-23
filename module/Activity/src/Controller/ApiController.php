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
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var ActivityQueryService
     */
    private ActivityQueryService $activityQueryService;

    /**
     * ApiController constructor.
     *
     * @param AclService $aclService
     * @param ActivityQueryService $activityQueryService
     */
    public function __construct(
        AclService $aclService,
        ActivityQueryService $activityQueryService,
    ) {
        $this->aclService = $aclService;
        $this->activityQueryService = $activityQueryService;
    }

    /**
     * List all activities.
     */
    public function listAction(): JsonModel
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
