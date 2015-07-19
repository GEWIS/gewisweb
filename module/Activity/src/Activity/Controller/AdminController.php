<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 19-7-15
 * Time: 11:39
 */

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Controller for all administrative activity actions
 */
class AdminController extends AbstractActionController
{
    /**
     * View the queue of not approved activities
     */
    public function queueAction()
    {
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activities = $activityService->getUnapprovedActivities();

        return ['activities' => $activities];
    }
}