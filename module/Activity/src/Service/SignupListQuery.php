<?php

declare(strict_types=1);

namespace Activity\Service;

use Activity\Mapper\SignupList as SignupListMapper;
use Activity\Model\SignupList as SignupListModel;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class SignupListQuery
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly SignupListMapper $signupListMapper,
    ) {
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
