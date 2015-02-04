<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;
use User\Model\User;

class Signup extends AbstractAclService
{
    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        //todo, this;
        return $this->getServiceManager()->get('activity_acl');
    }

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activitySignup';
    }

    public function isSignedUp(\Activity\Model\Activity $activity, \Decision\Model\Member $user)
    {
        $this->allowedOrException('view', 'activitySignup', 'signup');


        $signupMapper = $this->getServiceManager()->get('activity_mapper_signup');
        return $signupMapper->isSignedUp($activity->get('id'), $user->getLidnr());

    }

    public function signUp(\Activity\Model\Activity $activity, \Decision\Model\Member $user)
    {
        $this->allowedOrException('signUp', 'activitySignup', 'signup');

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        $signup = new \Activity\Model\ActivitySignup();
        $signup->setAcitivityId($activity->get('id'));
        $signup->setUserId($user->getLidnr());

        $em->persist($signup);
        $em->flush();
    }
}
