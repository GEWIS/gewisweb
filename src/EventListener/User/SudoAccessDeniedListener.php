<?php

declare(strict_types=1);

namespace App\EventListener\User;

use App\Security\User\Firewall;
use App\Security\User\SudoVoter;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

use function in_array;

/**
 * Intercepts an `AccessDeniedException` carrying the `SUDO` attribute and redirects to the sudo-confirmation route of
 * the firewall the request matches.
 *
 * Runs at priority 10, ahead of Symfony's per-firewall `ExceptionListener` (priority 1), which would otherwise redirect
 * an `IS_AUTHENTICATED_REMEMBERED` user to the login form. For sudo, a remember-me session must be allowed to step up
 * by re-typing the password instead. `stopPropagation()` ensures the built-in listener does not run for sudo denials;
 * other denials fall through unchanged.
 */
#[AsEventListener(
    event: ExceptionEvent::class,
    priority: 10,
)]
final class SudoAccessDeniedListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
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
            !in_array(
                SudoVoter::ATTRIBUTE,
                $accessDenied->getAttributes(),
                true,
            )
        ) {
            return;
        }

        $request = $event->getRequest();
        $firewall = $this->firewallMap->getFirewallConfig($request)?->getName();
        if (null === $firewall) {
            return;
        }

        $confirmRoute = Firewall::tryFrom($firewall)?->sudoConfirmRoute();
        if (null === $confirmRoute) {
            return;
        }

        // Only carry the original URL forward for safe (idempotent) methods. A POST/DELETE cannot be replayed via a
        // 302, so for those we send the user to the bare confirm page; they will retry the action after sudo.
        $params = $request->isMethodSafe()
            ? ['next' => $request->getRequestUri()]
            : [];

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate(
                $confirmRoute,
                $params,
            ),
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
