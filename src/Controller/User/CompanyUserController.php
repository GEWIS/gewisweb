<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserTypes;
use App\Entity\User\PasswordReset;
use App\Entity\User\User;
use App\Message\User\PasswordResetRequestEmail;
use App\Repository\User\PasswordResetRepository;
use App\Security\User\HandlerRegistry;
use App\Service\User\SessionManager;
use Override;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function bin2hex;

#[Route(
    path: '/company',
    name: 'company_user_',
)]
class CompanyUserController extends AbstractSecurityController
{
    public function __construct(
        TranslatorInterface $translator,
        SessionManager $sessionManager,
        HandlerRegistry $registry,
        #[Autowire(service: 'security.firewall.map')]
        FirewallMap $firewallMap,
        private readonly PasswordResetRepository $passwordResetRepository,
    ) {
        parent::__construct(
            $translator,
            $sessionManager,
            $registry,
            $firewallMap,
            routePrefix: 'company_user_',
            userType: UserTypes::CompanyUser,
        );
    }

    #[Override]
    protected function passwordResetRequestFormOptions(): array
    {
        return ['require_membership' => false];
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    #[Override]
    protected function buildPasswordResetCredentialKey(FormInterface $form): string
    {
        return bin2hex($form->get('email')->getData());
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    #[Override]
    protected function buildPasswordResetMessage(FormInterface $form): PasswordResetRequestEmail
    {
        return new PasswordResetRequestEmail(
            UserTypes::CompanyUser,
            $form->get('email')->getData(),
        );
    }

    #[Override]
    protected function passwordResetSessionKey(): string
    {
        return '_pwr_id_company_user';
    }

    #[Override]
    protected function resolvePasswordResetTarget(
        PasswordReset $passwordReset,
    ): User|CompanyUser|null {
        return $passwordReset->getCompanyUser();
    }

    #[Override]
    protected function deletePasswordResetsForTarget(PasswordReset $passwordReset): void
    {
        $companyUser = $passwordReset->getCompanyUser();
        if (null === $companyUser) {
            return;
        }

        $this->passwordResetRepository->deleteAllForCompanyUser($companyUser);
    }
}
