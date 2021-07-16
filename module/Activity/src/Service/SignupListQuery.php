<?php

namespace Activity\Service;

use Activity\Mapper\SignupList;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class SignupListQuery
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var SignupList
     */
    private $signupListMapper;
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        SignupList $signupListMapper,
        AclService $aclService
    ) {
        $this->translator = $translator;
        $this->signupListMapper = $signupListMapper;
        $this->aclService = $aclService;
    }

    /**
     * @param int $signupListId
     * @param int $activityId
     *
     * @return \Activity\Model\SignupList|null
     */
    public function getSignupListByActivity($signupListId, $activityId)
    {
        if (!$this->aclService->isAllowed('view', 'signupList')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view sign-up lists'));
        }

        return $this->signupListMapper->getSignupListByIdAndActivity($signupListId, $activityId);
    }

    public function getSignupListsOfActivity($activityId)
    {
        if (!$this->aclService->isAllowed('view', 'signupList')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view sign-up lists'));
        }

        return $this->signupListMapper->getSignupListsOfActivity($activityId);
    }
}
