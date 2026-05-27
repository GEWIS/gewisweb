<?php

declare(strict_types=1);

namespace App\EventListener\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Security\User\MfaPolicy;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use function array_intersect;
use function assert;

/**
 * Converts a `ROLE_ADMIN` / `ROLE_BOARD` access denial into a redirect to the MFA enrolment page when the current user
 * is in scope per {@see MfaPolicy} but has not yet enrolled.
 *
 * Runs at priority 9, just below {@see SudoAccessDeniedListener} (priority 10), so a denial that mentions both `SUDO`
 * and a role still routes to the sudo confirmation page first. After sudo is granted, the user retries the request and
 * lands here if MFA is still missing.
 *
 * Unrelated access denials (e.g. `ROLE_COMPANY_ADMIN`, expression voters) fall through to Symfony's default 403 page.
 */
#[AsEventListener(
    event: ExceptionEvent::class,
    priority: 9,
)]
final class MfaEnforcementListener
{
    private const array ENROL_ROUTES = [
        'main' => 'user_mfa_enable',
    ];

    private const array MFA_GATED_ATTRIBUTES = [
        UserRoles::Admin->value,
        UserRoles::Board->value,
    ];

    public function __construct(
        private readonly MfaPolicy $mfaPolicy,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'security.firewall.map')]
        private readonly FirewallMap $firewallMap,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $accessDenied = $this->findAccessDenied($event->getThrowable());
        if (null === $accessDenied) {
            return;
        }

        if (
            [] === array_intersect(
                self::MFA_GATED_ATTRIBUTES,
                $accessDenied->getAttributes(),
            )
        ) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof User) {
            return;
        }

        if (
            !$this->mfaPolicy->isRequiredFor($user)
            || $this->mfaPolicy->hasEnrolled($user)
        ) {
            return;
        }

        $request = $event->getRequest();
        $firewall = $this->firewallMap->getFirewallConfig($request)?->getName();
        if (null === $firewall) {
            return;
        }

        $enrolRoute = self::ENROL_ROUTES[$firewall] ?? null;
        if (null === $enrolRoute) {
            return;
        }

        $session = $request->getSession();
        assert($session instanceof Session);
        $session->getFlashBag()->add(
            AlertTypes::Warning->value,
            $this->translator->trans(
                'Multi-factor authentication is required for your role. Privileged actions are unavailable until you enable it.', // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
            ),
        );

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate($enrolRoute),
        ));
        $event->stopPropagation();
    }

    private function findAccessDenied(?Throwable $throwable): ?AccessDeniedException
    {
        while (null !== $throwable) {
            if ($throwable instanceof AccessDeniedException) {
                return $throwable;
            }

            $throwable = $throwable->getPrevious();
        }

        return null;
    }
}
