<?php

namespace Activity\Service;

use Activity\Mapper\SignupList as SignupListMapper;
use Activity\Model\SignupList as SignupListModel;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class SignupListQuery
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var SignupListMapper
     */
    private SignupListMapper $signupListMapper;

    /**
     * @param Translator $translator
     * @param SignupListMapper $signupListMapper
     * @param AclService $aclService
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        SignupListMapper $signupListMapper,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->signupListMapper = $signupListMapper;
    }

    /**
     * @param int $signupListId
     * @param int $activityId
     *
     * @return SignupListModel|null
     */
    public function getSignupListByActivity(
        int $signupListId,
        int $activityId,
    ): ?SignupListModel {
        if (!$this->aclService->isAllowed('view', 'signupList')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view sign-up lists'));
        }

        return $this->signupListMapper->getSignupListByIdAndActivity($signupListId, $activityId);
    }
}
