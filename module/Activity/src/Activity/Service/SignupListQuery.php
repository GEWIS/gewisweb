<?php

namespace Activity\Service;

use Activity\Mapper\SignupList;
use Application\Service\AbstractAclService;
use User\Permissions\NotAllowedException;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class SignupListQuery extends AbstractAclService implements ServiceManagerAwareInterface
{
    /**
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('activity_acl');
    }

    public function getSignupListByActivity($signupListId, $activityId)
    {
        if (!$this->isAllowed('view', 'signupList')) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to view sign-up lists')
            );
        }

        $signupListMapper = $this->getServiceManager()->get('activity_mapper_signuplist');

        return $signupListMapper->getSignupListByIdAndActivity($signupListId, $activityId);
    }

    public function getSignupListsOfActivity($activityId)
    {
        if (!$this->isAllowed('view', 'signupList')) {
            $translator = $this->getTranslator();
            throw new NotAllowedException(
                $translator->translate('You are not allowed to view sign-up lists.')
            );
        }

        $signupListMapper = $this->getServiceManager()->get('activity_mapper_signuplist');

        return $signupListMapper->getSignupListsOfActivity($activityId);
    }

    /**
     * Get the activity mapper.
     *
     * @return SignupList
     */
    public function getActivityMapper()
    {
        return $this->sm->get('activity_mapper_signuplist');
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
}
