<?php

declare(strict_types=1);

namespace User\Controller;

use Decision\Mapper\Member as MemberMapper;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use Override;
use User\Permissions\NotAllowedException;
use User\Service\AclService;

class UserAdminController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly MemberMapper $memberMapper,
    ) {
    }

    #[Override]
    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('view_status', 'user')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to administer users'),
            );
        }

        return new ViewModel([
            'members' => $this->memberMapper->findAllWithUserDetails(),
        ]);
    }
}
