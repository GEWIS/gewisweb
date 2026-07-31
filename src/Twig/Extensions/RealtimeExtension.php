<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Service\User\SessionManager;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\RuntimeException as MercureRuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function sprintf;

/**
 * Exposes `realtime_topics()`: the Mercure topics the current request may subscribe to. Everyone gets the broadcast
 * topic; someone who has finished signing in also gets their per-user topic and, when the device carries a
 * managed-session cookie, its per-session topic. Resolving the firewall and (secret) series in PHP keeps it testable
 * and out of the template. `realtime_authorize()` mints the single subscribe cookie for those topics.
 */
class RealtimeExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly SessionManager $sessionManager,
        #[Autowire(service: 'security.firewall.map')]
        private readonly FirewallMap $firewallMap,
        private readonly Authorization $authorization,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'realtime_topics',
                $this->realtimeTopics(...),
            ),
            new TwigFunction(
                'realtime_authorize',
                $this->realtimeAuthorize(...),
            ),
        ];
    }

    /**
     * @param string[] $topics
     */
    public function realtimeAuthorize(array $topics): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return;
        }

        try {
            $this->authorization->setCookie(
                $request,
                $topics,
            );
        } catch (MercureRuntimeException) {
            // Thrown when the hub sits on a different host than the request (a hostless request under test, or a
            // misconfigured hub). Leave realtime off for this page rather than failing the whole render.
        }
    }

    /**
     * @return string[]
     */
    public function realtimeTopics(): array
    {
        $topics = ['gewis/public'];

        // Someone who still has a second factor to clear has a user on the token but has not signed in yet, so they
        // get no more than a passer-by does. Anything else would push their notifications to whoever holds the
        // password.
        if (!$this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $topics;
        }

        $user = $this->security->getUser();
        $request = $this->requestStack->getMainRequest();
        if (
            !$user instanceof UserInterface
            || null === $request
        ) {
            return $topics;
        }

        $firewall = $this->firewallMap->getFirewallConfig($request)?->getName();
        if (null === $firewall) {
            return $topics;
        }

        $topics[] = sprintf(
            'gewis/user/%s/%s',
            $firewall,
            $user->getUserIdentifier(),
        );

        $series = $this->sessionManager->currentSeries(
            $request,
            $firewall,
        );
        if (null !== $series) {
            $topics[] = sprintf(
                'gewis/session/%s/%s',
                $firewall,
                $series,
            );
        }

        return $topics;
    }
}
