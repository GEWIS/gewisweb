<?php

declare(strict_types=1);

namespace App\EventListener\User;

use App\Repository\User\SessionRepository;
use App\Security\User\HandlerRegistry;
use App\Security\User\UserAgentParser;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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

use function assert;
use function preg_replace;
use function strtolower;

/**
 * For every authenticated request:
 *  1. If the remember-me cookie is missing OR references a series not on file, force a logout (`managed_sessions` row
 *     removed if present, PHP session invalidated, redirect to log in). Our policy is that a remember-me cookie is
 *     always issued at login, so absence is anomalous and warrants a hard reset rather than silent recovery. Otherwise,
 *     a stolen PHP session cookie could self-upgrade to a persistent remember-me cookie.
 *  2. Otherwise (cookie present + row found) rebind `phpSessionId` if the stored value has drifted from the current
 *     request's session ID (happens for example when the SessionAuthenticationStrategy migrates the ID after
 *     rememberme-resumed login), and bump `lastUsedAt` (throttled), so the security UI's "Last seen" column tracks real
 *     activity rather than just cookie-rotation moments.
 *
 * Unauthenticated requests are left alone. Mid-2FA requests are also left alone, since scheb's `TwoFactorToken` does
 * not grant `IS_AUTHENTICATED_REMEMBERED` and the remember-me cookie is not issued until 2FA completes.
 *
 * The lookup pivot here is the cookie's **series**, not the PHP session ID. Series is the authoritative identifier (it
 * does not change across token rotations or session migrations), whereas `phpSessionId` is just the pointer we keep so
 * "log out this device" can destroy the matching Valkey entry directly.
 */
#[AsEventListener(event: RequestEvent::class)]
final class StaleSessionGuardListener
{
    /**
     * Firewall name -> login route.
     *
     * Hardcoded for the same reason as {@see SudoAccessDeniedListener}'s CONFIRM_ROUTES: Symfony's `FirewallMap` /
     * `FirewallConfig` does not expose any per-firewall route metadata. We cannot easily obtain the
     * form_login.login_path` in another way, so we have it here for direct lookup.
     */
    private const array LOGIN_ROUTES = [
        'main' => 'user_login',
        'company' => 'company_user_login',
    ];

    /**
     * Do not write lastUsedAt more than once per this many seconds to spare the DB.
     */
    private const int LAST_USED_THROTTLE_SECONDS = 180;

    public function __construct(
        private readonly SessionRepository $repository,
        private readonly HandlerRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly Security $security,
        #[Autowire(service: 'security.firewall.map')]
        private readonly FirewallMap $firewallMap,
        private readonly UserAgentParser $userAgentParser,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        $phpSessionId = $request->getSession()->getId();
        if ('' === $phpSessionId) {
            return;
        }

        $firewall = $this->firewallMap->getFirewallConfig($request)?->getName();
        if (null === $firewall) {
            return;
        }

        $handler = $this->registry->get($firewall);
        if (null === $handler) {
            return;
        }

        $series = $handler->getSeriesFromCookie($request);

        // Missing cookie on an authenticated request -> tear down.
        if (null === $series) {
            $orphan = $this->repository->findOneByPhpSessionId($phpSessionId);
            if (null !== $orphan) {
                $this->entityManager->remove($orphan);
                $this->entityManager->flush();
            }

            $this->forceLogout(
                $firewall,
                $event,
            );

            return;
        }

        // Cookie present -> look the row up by its series (the authoritative identifier).
        $managedSession = $this->repository->findOneBySeries($series);

        // Cookie references a series with no row -> also anomalous, tear down.
        if (null === $managedSession) {
            $this->forceLogout(
                $firewall,
                $event,
            );

            return;
        }

        // Cross-firewall token replay attempt (a cookie from one firewall on another firewall's URL). Refuse -> leave
        // the data alone and let the request fall through unauthenticated.
        if ($managedSession->getFirewallName() !== $firewall) {
            return;
        }

        // Fingerprint check: compare the current request's browser+OS family (names sans version) against what was
        // stored at login. Versions are intentionally ignored so legit updates (Firefox 124 -> 140) do not trip the
        // gate. A mismatch on either side suggests the cookie pair has been replayed from a different device -> tear
        // down.
        $currentMeta = $this->userAgentParser->parse($request->headers->get('User-Agent', ''));
        $storedBrowser = self::extractName($managedSession->getBrowser());
        $currentBrowser = self::extractName($currentMeta['browser']);
        $storedOs = self::extractName($managedSession->getOperatingSystem());
        $currentOs = self::extractName($currentMeta['operatingSystem']);

        $browserMismatch = null !== $storedBrowser && null !== $currentBrowser && $storedBrowser !== $currentBrowser;
        $osMismatch = null !== $storedOs && null !== $currentOs && $storedOs !== $currentOs;

        if (
            $browserMismatch
            || $osMismatch
        ) {
            $this->logger?->warning(
                'User-agent family mismatch -> tearing down session.',
                [
                    'series' => $series,
                    'user' => $managedSession->getUserIdentifier(),
                    'firewall' => $firewall,
                    'stored_browser' => $managedSession->getBrowser(),
                    'current_browser' => $currentMeta['browser'],
                    'stored_os' => $managedSession->getOperatingSystem(),
                    'current_os' => $currentMeta['operatingSystem'],
                ],
            );
            $this->entityManager->remove($managedSession);
            $this->entityManager->flush();
            $this->forceLogout(
                $firewall,
                $event,
            );

            return;
        }

        $changed = false;

        // Rebind phpSessionId if it has drifted (Symfony's session migration on a rememberme-resumed login changes the
        // ID between createSession and the next request).
        if ($managedSession->getPhpSessionId() !== $phpSessionId) {
            $managedSession->setPhpSessionId($phpSessionId);
            $changed = true;
        }

        // Throttled lastUsedAt bump so the security UI's "Last seen" reflects real activity rather than only the
        // moments of token rotation.
        $now = new DateTimeImmutable();
        $staleAfter = $now->modify('-' . self::LAST_USED_THROTTLE_SECONDS . ' seconds');
        if ($managedSession->getLastUsedAt() < $staleAfter) {
            $managedSession->setLastUsedAt($now);
            $changed = true;
        }

        if (!$changed) {
            return;
        }

        $this->entityManager->flush();
    }

    private function forceLogout(
        string $firewall,
        RequestEvent $event,
    ): void {
        // Clear the in-memory security token BEFORE invalidating the session. If we skip this, Symfony's
        // ContextListener writes the still-active token back to the freshly-created PHP session on kernel.response, and
        // the next request is silently re-authenticated -> this listener fires again -> infinite redirect loop.
        $this->tokenStorage->setToken(null);
        $event->getRequest()->getSession()->invalidate();

        $loginRoute = self::LOGIN_ROUTES[$firewall] ?? null;
        if (null === $loginRoute) {
            return;
        }

        $session = $event->getRequest()->getSession();
        assert($session instanceof Session);
        $session->getFlashBag()->add(
            'warning',
            $this->translator->trans('Your session was ended for security reasons. Please sign in again.'),
        );

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate($loginRoute),
        ));
    }

    /**
     * Strip the trailing major-version token ("Firefox 124" -> "firefox") and lower-case for stable comparison.
     */
    private static function extractName(?string $combined): ?string
    {
        if (null === $combined) {
            return null;
        }

        return strtolower(preg_replace('/\s+\d+$/', '', $combined) ?? $combined);
    }
}
