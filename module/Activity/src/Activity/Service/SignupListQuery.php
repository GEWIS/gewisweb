<?php

namespace Activity\Service;

use Activity\Mapper\SignupList;
use Application\Service\AbstractAclService;
use User\Permissions\NotAllowedException;
use Zend\Mvc\I18n\Translator;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class SignupListQuery extends AbstractAclService implements ServiceManagerAwareInterface
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->sm->get('activity_acl');
    }

    public function getSignupListByActivity($signupListId, $activityId)
    {
        if (!$this->isAllowed('view', 'signupList')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view sign-up lists')
            );
        }

        $signupListMapper = $this->sm->get('activity_mapper_signuplist');

        return $signupListMapper->getSignupListByIdAndActivity($signupListId, $activityId);
    }

    public function getSignupListsOfActivity($activityId)
    {
        if (!$this->isAllowed('view', 'signupList')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view sign-up lists')
            );
        }

        $signupListMapper = $this->sm->get('activity_mapper_signuplist');

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
