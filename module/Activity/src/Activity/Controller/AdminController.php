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
        $perPage = 5;
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $unapprovedActivities = $activityService->getUnapprovedActivities();
        $approvedActivities = $activityService->getApprovedActivities();
        $disapprovedActivities = $activityService->getDisapprovedActivities();

        return [
            'unapprovedActivities' => array_slice($unapprovedActivities, 0, $perPage),
            'approvedActivities' => array_slice($approvedActivities, 0, $perPage),
            'disapprovedActivities' => array_slice($disapprovedActivities, 0, $perPage),
            'moreUnapprovedActivities' => count($unapprovedActivities) > $perPage,
            'moreApprovedActivites' => count($approvedActivities) > $perPage,
            'moreDisapprovedActivities' => count($disapprovedActivities) > $perPage
        ];
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
     * View all the unapproved activities with paginator
     * @return array
     */
    public function queueUnapprovedAction()
    {
        $page = (int) $this->params('page', 1);
        return $this->viewStatus(Activity::STATUS_TO_APPROVE, $page);
    }


    /**
     * View activities with a certain status
     *
     * @param integer $status
     * @param integer $page
     * @return array
     */
    protected function viewStatus($status, $page = 1)
    {
        $activityService = $this->getServiceLocator()->get('activity_service_activity');
        $activities = $activityService->getActivityPaginatorByStatus($status, $page);

        return [
            'activities' => $activities,
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
     * Reset the approval status of an activity
     */
    public function resetAction()
    {
        return $this->setApprovalStatus('reset');
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
            case 'reset':
                $activityService->reset($activity);
                break;
            default:
                throw new \InvalidArgumentException('No sutch status ' . $status);

        }

        return $this->redirect()->toRoute('admin_activity/queue');
    }
}