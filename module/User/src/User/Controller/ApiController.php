<?php

/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @see      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 *
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace User\Controller;

use Decision\Service\MemberInfo;
use Laminas\Mvc\Controller\AbstractActionController;
use User\Service\User;

class ApiController extends AbstractActionController
{
    /**
     * @var User
     */
    private $userService;

    /**
     * @var MemberInfo
     */
    private $memberInfoService;

    public function __construct(User $userService, MemberInfo $memberInfoService)
    {
        $this->userService = $userService;
        $this->memberInfoService = $memberInfoService;
    }

    public function validateAction()
    {
        if ($this->userService->hasIdentity()) {
            $identity = $this->userService->getIdentity();
            $response = $this->getResponse();
            $response->setStatusCode(200);
            $headers = $response->getHeaders();
            $headers->addHeaderLine('GEWIS-MemberID', $identity->getLidnr());
            if (null != $identity->getMember()) {
                $member = $identity->getMember();
                $name = $member->getFullName();
                $headers->addHeaderLine('GEWIS-MemberName', $name);
                $headers->addHeaderLine('GEWIS-MemberEmail', $member->getEmail());
                $memberships = $this->memberInfoService->getOrganMemberships($member);
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
}
