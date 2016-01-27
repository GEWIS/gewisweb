<?php

namespace Activity\Controller;

use Activity\Model\Activity;
use Activity\Service\Signup;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Activity\Form\ModifyRequest as RequestForm;
use Zend\View\Model\ViewModel;

/**
 * Controller that gives some additional details for activities, such as a list of email adresses
 * or an export function specially tailored for the organizer.
 */
class OrganizerController extends AbstractActionController
{
    /**
     * Show the email adresses belonging to an Activity
     */
    public function emailAction()
    {
        die('yay');
    }
}
