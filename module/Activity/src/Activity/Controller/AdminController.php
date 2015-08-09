<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 19-7-15
 * Time: 11:39
 */

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Activity\Model\Activity;

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

    /**
     * View one activity.
     */
    public function viewAction()
    {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');

        /** @var $activity Activity*/
        $activity = $activityService->getActivity($id);

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        return [
            'activity' => $activity,
        ];
    }

    /**
     * Approve of an activity
     */
    public function approveAction()
    {
        return $this->setApprovalStatus('approve');
    }

    /**
     * Disapprove an activity
     */
    public function disapproveAction()
    {
        return $this->setApprovalStatus('disapprove');
    }

    /**
     * Set the approval status of the activity requested
     *
     * @param $status
     * @return array|\Zend\Http\Response
     */
    protected function setApprovalStatus($status)
    {
        $id = (int) $this->params('id');
        $activityService = $this->getServiceLocator()->get('activity_service_activity');

        /** @var $activity Activity*/
        $activity = $activityService->getActivity($id);

        if (is_null($activity)) {
            return $this->notFoundAction();
        }

        switch ($status) {
            case 'approve':
                $activityService->approve($activity);
                break;
            case 'disapprove':
                $activityService->disapprove($activity);
                break;
            default:
                throw new \InvalidArgumentException('No sutch status ' . $status);

        }

        return $this->redirect()->toRoute('activity/admin_queue');
    }
}