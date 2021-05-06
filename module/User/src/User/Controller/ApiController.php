<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;

class ApiController extends AbstractActionController
{
    public function validateAction()
    {
        $userService = $this->getUserService();
        if ($userService->hasIdentity()) {
            $identity = $userService->getIdentity();
            $response = $this->getResponse();
            $response->setStatusCode(200);
            $headers = $response->getHeaders();
            $headers->addHeaderLine('GEWIS-MemberID', $identity->getLidnr());
            if ($identity->getMember() != null) {
                $member = $identity->getMember();
                $name = $member->getFullName();
                $headers->addHeaderLine('GEWIS-MemberName', $name);
                $headers->addHeaderLine('GEWIS-MemberEmail', $member->getEmail());
                $memberships = $this->getMemberService()->getOrganMemberships($member);
                $headers->addHeaderLine('GEWIS-MemberGroups', implode(',', array_keys($memberships)));
                return $response;
            }
            $headers->addHeaderLine('GEWIS-MemberName', '');
            return $response;
        }
        $response = $this->getResponse();
        $response->setStatusCode(401);
        return $response;
    }

    /**
     * Get a user service.
     *
     * @return \User\Service\User
     */
    protected function getUserService()
    {
        return $this->getServiceLocator()->get('user_service_user');
    }

    /**
     * Get the member service.
     *
     * @return Decision\Service\Member
     */
    public function getMemberService()
    {
        return $this->getServiceLocator()->get('decision_service_member');
    }
}
