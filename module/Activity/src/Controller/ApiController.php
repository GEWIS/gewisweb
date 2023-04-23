<?php

declare(strict_types=1);

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
    public function __construct(
        private readonly AclService $aclService,
        private readonly ActivityQueryService $activityQueryService,
    ) {
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
