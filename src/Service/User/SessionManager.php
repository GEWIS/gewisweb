<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\Session;
use App\Repository\User\SessionRepository;
use App\Security\User\HandlerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Redis;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

use function array_filter;
use function array_values;
use function count;

/**
 * High-level facade for managing a user's active sessions.
 *
 * Every method is scoped to a specific firewall. So terminating all `main` sessions never touches `company` sessions,
 * and vice versa. When a row is removed the matching PHP session is also destroyed in Valkey, so the device is logged
 * out on its next request.
 */
final class SessionManager
{
    public function __construct(
        private readonly SessionRepository $repository,
        private readonly HandlerRegistry $registry,
        private readonly EntityManagerInterface $em,
        #[Autowire(service: 'Redis')]
        private readonly Redis $redis,
        #[Autowire(param: 'app.session_prefix')]
        private readonly string $sessionPrefix,
    ) {
    }

    /**
     * Return all active (non-expired) sessions for the user on the given firewall.
     *
     * @return Session[]
     */
    public function getActiveSessions(
        UserInterface $user,
        string $firewallName,
    ): array {
        return $this->repository->findActiveByUserOnFirewall(
            $user->getUserIdentifier(),
            $firewallName,
        );
    }

    /**
     * Returns the series identifier for the device making this request or `null` if there's no managed-session cookie
     * (e.g. a fresh login that has not received its persistent cookie yet).
     */
    public function currentSeries(
        Request $request,
        string $firewallName,
    ): ?string {
        return $this->registry->get($firewallName)?->getSeriesFromCookie($request);
    }

    public function findSession(
        UserInterface $user,
        string $series,
        string $firewallName,
    ): ?Session {
        $session = $this->repository->findOneBySeries($series);

        if (
            null === $session
            || $session->getUserIdentifier() !== $user->getUserIdentifier()
            || $session->getFirewallName() !== $firewallName
        ) {
            return null;
        }

        return $session;
    }

    public function terminateSession(
        UserInterface $user,
        string $series,
        Request $request,
        string $firewallName,
    ): bool {
        $session = $this->findSession(
            $user,
            $series,
            $firewallName,
        );

        if (null === $session) {
            return false;
        }

        // Same guard as terminateAllExceptCurrent(): if a zombie row points at the live PHP session ID, destroying it
        // would wipe the caller's session in Valkey and silently log them out (and, via remember-me, drop them back at
        // the sudo-confirm prompt because _sudo_granted_at lived on the wiped session). So, we must drop the DB row but
        // skip the destroy().
        if ($session->getPhpSessionId() === $request->getSession()->getId()) {
            $this->em->remove($session);
            $this->em->flush();

            return true;
        }

        $this->destroyAndRemove($session);

        return true;
    }

    public function terminateAllExceptCurrent(
        UserInterface $user,
        Request $request,
        string $firewallName,
    ): int {
        $handler = $this->registry->get($firewallName);
        $currentSeries = $handler?->getSeriesFromCookie($request);

        // No identifiable "current" device -> refuse to scope "all others". Falling through would terminate every
        // session including the caller's, which is a surprise self-logout the StaleSessionGuardListener should have
        //prevented us reaching, but let's be defensive and not do that if something's weird with the cookie.
        if (null === $currentSeries) {
            return 0;
        }

        $currentPhpSessionId = $request->getSession()->getId();

        // Filter on BOTH series AND phpSessionId. The series guard covers the intended row; the phpSessionId guard
        // covers zombie rows that happen to point at the active Valkey entry (e.g. residue from a previous login where
        // logout left the row behind, then a coincidence in the PHP session ID space). Destroying any such row would
        // call sessionHandler->destroy() on the live PHP session and silently log the caller out.
        $sessions = array_values(array_filter(
            $this->repository->findAllByUserOnFirewall(
                $user->getUserIdentifier(),
                $firewallName,
            ),
            static fn (Session $s): bool => $s->getSeries() !== $currentSeries
                && $s->getPhpSessionId() !== $currentPhpSessionId,
        ));

        foreach ($sessions as $session) {
            $this->destroyAndRemove($session);
        }

        return count($sessions);
    }

    public function terminateAll(
        UserInterface $user,
        string $firewallName,
    ): int {
        $sessions = $this->repository->findAllByUserOnFirewall(
            $user->getUserIdentifier(),
            $firewallName,
        );

        foreach ($sessions as $session) {
            $this->destroyAndRemove($session);
        }

        return count($sessions);
    }

    /**
     * Destroys the device's PHP session in Valkey (if known) and removes the managed-sessions row.
     *
     * We delete the Valkey key directly rather than going through {@see RedisSessionHandler::destroy()} because it and
     * Symfony's {@see AbstractSessionHandler::destroy()} always queues a Set-Cookie: GWS_SESSION=; expires=...`
     * response header to clear the session cookie - regardless of which session ID was destroyed.
     *
     * So calling that here would silently log the caller out (their cookie gets deleted), then remember-me would
     * re-auth them into a fresh session with no `_sudo_granted_at`, dropping them at the sudo-confirm prompt.
     *
     * Deleting the key directly hits Valkey only and leaves the caller's cookie alone. `DEL` is a no-op if the key is
     * already gone.
     */
    private function destroyAndRemove(Session $session): void
    {
        $phpSessionId = $session->getPhpSessionId();

        if ('' !== $phpSessionId) {
            $this->redis->del($this->sessionPrefix . $phpSessionId);
        }

        $this->em->remove($session);
        $this->em->flush();
    }
}
