<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class SignupListQuery extends AbstractAclService implements ServiceManagerAwareInterface
{
    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
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
        return 'signupList';
    }

    public function getSignupListByActivity($signupListId, $activityId)
    {
        if (!$this->isAllowed('view', 'signupList')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view sign-up lists')
            );
        }

        $signupListMapper = $this->getServiceManager()->get('activity_mapper_signuplist');
        $signupList = $signupListMapper->getSignupListByIdAndActivity($signupListId, $activityId);

        return $signupList;
    }

    public function getSignupListsOfActivity($activityId)
    {
        if (!$this->isAllowed('view', 'signupList')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view sign-up lists.')
            );
        }

        $signupListMapper = $this->getServiceManager()->get('activity_mapper_signuplist');
        $signupLists = $signupListMapper->getSignupListsOfActivity($activityId);

        return $signupLists;
    }

    /**
     * Get the activity mapper.
     *
     * @return \Activity\Mapper\SignupList
     */
    public function getActivityMapper()
    {
        return $this->sm->get('activity_mapper_signuplist');
    }
}
