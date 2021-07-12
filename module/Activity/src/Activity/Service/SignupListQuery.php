<?php

namespace Activity\Service;

use Activity\Mapper\SignupList;
use Application\Service\AbstractAclService;
use User\Model\User;
use User\Permissions\NotAllowedException;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;

class SignupListQuery extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var SignupList
     */
    private $signupListMapper;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        SignupList $signupListMapper
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->signupListMapper = $signupListMapper;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * @param $signupListId
     * @param $activityId
     * @return \Activity\Model\SignupList|null
     */
    public function getSignupListByActivity($signupListId, $activityId)
    {
        if (!$this->isAllowed('view', 'signupList')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view sign-up lists')
            );
        }

        return $this->signupListMapper->getSignupListByIdAndActivity($signupListId, $activityId);
    }

    public function getSignupListsOfActivity($activityId)
    {
        if (!$this->isAllowed('view', 'signupList')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view sign-up lists')
            );
        }

        return $this->signupListMapper->getSignupListsOfActivity($activityId);
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
