<?php

declare(strict_types=1);

namespace App\EventListener\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\CompanyUser;
use App\Security\User\Firewall;
use App\Security\User\UserChecker;
use App\Service\User\CompanyUserAccessPolicy;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Ends a portal session the moment the representative behind it may no longer be there: the board shut them out, or the
 * company's contract lapsed while they were signed in. Nothing marks either event on the session itself, so it is
 * checked per request rather than waiting for the next sign-in.
 */
#[AsEventListener(event: RequestEvent::class)]
final readonly class CompanyAccessGuardListener
{
    public function __construct(
        private CompanyUserAccessPolicy $companyUserAccessPolicy,
        private Security $security,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        #[Autowire(service: 'security.firewall.map')]
        private FirewallMap $firewallMap,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (Firewall::Company->value !== $this->firewallMap->getFirewallConfig($request)?->getName()) {
            return;
        }

        $companyUser = $this->security->getUser();
        if (!$companyUser instanceof CompanyUser) {
            return;
        }

        if (
            $this->companyUserAccessPolicy->isAllowed(
                $companyUser,
                new DateTimeImmutable('now'),
            )
        ) {
            return;
        }

        // Clear the token before invalidating the session, so the context listener cannot write it back on
        // kernel.response and re-authenticate the next request into the same dead end.
        $this->tokenStorage->setToken(null);

        $session = $request->getSession();
        $session->invalidate();

        if ($session instanceof Session) {
            $session->getFlashBag()->add(
                AlertTypes::Warning->value,
                $this->translator->trans(UserChecker::BLANKET_DENIAL),
            );
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate(Firewall::Company->loginRoute()),
        ));
    }
}
