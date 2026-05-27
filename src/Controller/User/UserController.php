<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserTypes;
use App\Entity\User\PasswordReset;
use App\Entity\User\User;
use App\Message\User\PasswordResetRequestEmail;
use App\Repository\User\PasswordResetRepository;
use App\Repository\User\UserRepository;
use App\Security\User\HandlerRegistry;
use App\Service\User\SessionManager;
use Override;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function bin2hex;
use function intval;
use function sprintf;

#[Route(
    path: '/user',
    name: 'user_',
)]
class UserController extends AbstractSecurityController
{
    public function __construct(
        TranslatorInterface $translator,
        SessionManager $sessionManager,
        HandlerRegistry $registry,
        #[Autowire(service: 'security.firewall.map')]
        FirewallMap $firewallMap,
        private readonly UserRepository $userRepository,
        private readonly PasswordResetRepository $passwordResetRepository,
    ) {
        parent::__construct(
            $translator,
            $sessionManager,
            $registry,
            $firewallMap,
            routePrefix: 'user_',
            userType: UserTypes::User,
        );
    }

    /**
     * Routed from {@see /config/routes.yaml} (`user_token`) rather than via an attribute, so it stays a plain locale-
     * less URL. Linked from {@see /templates/partials/application/main-nav.html.twig}.
     */
    public function token(): Response
    {
        return $this->render('user/token.html.twig');
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    #[Override]
    protected function buildPasswordResetCredentialKey(FormInterface $form): string
    {
        return bin2hex(sprintf(
            '%s-%s',
            $form->get('membershipNumber')->getData(),
            $form->get('email')->getData(),
        ));
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    #[Override]
    protected function buildPasswordResetMessage(FormInterface $form): PasswordResetRequestEmail
    {
        return new PasswordResetRequestEmail(
            UserTypes::User,
            $form->get('email')->getData(),
            intval($form->get('membershipNumber')->getData()),
        );
    }

    #[Override]
    protected function passwordResetSessionKey(): string
    {
        return '_pwr_id_user';
    }

    #[Override]
    protected function resolvePasswordResetTarget(
        PasswordReset $passwordReset,
    ): User|CompanyUser|null {
        $member = $passwordReset->getMember();
        if (null === $member) {
            return null;
        }

        // Instantiate a User on first activation (Member exists from GEWISDB sync, no User row yet). The new row is
        // only persisted by the abstract base after a successful, validated password submission.
        $user = $this->userRepository->find($member->getLidnr());
        if (null === $user) {
            $user = new User();
            $user->setLidnr($member->getLidnr());
            $user->setMember($member);
        }

        return $user;
    }

    #[Override]
    protected function deletePasswordResetsForTarget(PasswordReset $passwordReset): void
    {
        $member = $passwordReset->getMember();
        if (null === $member) {
            return;
        }

        $this->passwordResetRepository->deleteAllForMember($member);
    }
}
