<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\MaintenanceStatus;
use App\Service\Application\MaintenanceStatusProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

use function file_get_contents;
use function in_array;
use function str_starts_with;

/**
 * Serves the maintenance page (and, in read-only mode, blocks writes) while maintenance is in effect. Two levels:
 *  - the `MAINTENANCE` env var forces full maintenance for everyone, for infra-level work where even admins should stay
 *    out (e.g. migrations at startup);
 *  - otherwise the app-level {@see \App\Entity\Application\MaintenanceWindow} covering right now decides, with admins
 *    bypassing it so they can keep working and turn it off again.
 *
 * Runs after the firewall so the admin bypass can see the authenticated user. A non-admin login is refused earlier, by
 * {@see \App\Security\User\UserChecker}, because the firewall handles the login before this listener runs.
 */
#[AsEventListener(
    event: RequestEvent::class,
    priority: 6,
)]
final readonly class MaintenanceListener
{
    /**
     * Sign-in flow routes that stay reachable under full maintenance, so a logged-out admin can authenticate and lift
     * it. A non-admin who reaches them is still refused at the credential check by the user checker.
     */
    private const array AUTHENTICATION_ROUTES = [
        'user_login',
        'user_mfa_challenge',
        'user_mfa_challenge_check',
        'user_sudo_confirm',
        'company_user_login',
        'company_user_mfa_challenge',
        'company_user_mfa_challenge_check',
        'company_user_sudo_confirm',
    ];

    public function __construct(
        private MaintenanceStatusProvider $maintenanceStatus,
        private Security $security,
        private TranslatorInterface $translator,
        #[Autowire('%env(bool:MAINTENANCE)%')]
        private bool $maintenanceEnv,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->maintenanceEnv) {
            $event->setResponse($this->maintenancePage());

            return;
        }

        $window = $this->maintenanceStatus->activeWindow();
        if (
            null === $window
            || $this->security->isGranted('ROLE_ADMIN')
        ) {
            return;
        }

        $request = $event->getRequest();

        if (MaintenanceStatus::Full === $window->getStatus()) {
            // A logged-out admin must still reach the sign-in flow (login, MFA, sudo) to authenticate and lift
            // maintenance; a non-admin who reaches it is refused at the credential check by the user checker.
            if ($this->isAuthenticationRoute($request)) {
                return;
            }

            $event->setResponse($this->maintenancePage());

            return;
        }

        if ($request->isMethodSafe()) {
            return;
        }

        // Read-only: keep the user on the site and tell them the write was refused, rather than dropping them on the
        // maintenance page.
        $this->flashReadOnly($request);
        $event->setResponse(new RedirectResponse(
            $this->returnUrl($request),
            Response::HTTP_SEE_OTHER,
        ));
    }

    private function flashReadOnly(Request $request): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $session->getFlashBag()->add(
            AlertTypes::Warning->value,
            $this->translator->trans(
                'The website is temporarily read-only for maintenance, so your change was not saved.', // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
            ),
        );
    }

    private function isAuthenticationRoute(Request $request): bool
    {
        return in_array(
            $request->attributes->get('_route'),
            self::AUTHENTICATION_ROUTES,
            true,
        );
    }

    private function returnUrl(Request $request): string
    {
        $referer = $request->headers->get('referer');
        if (
            null !== $referer
            && str_starts_with(
                $referer,
                $request->getSchemeAndHttpHost(),
            )
        ) {
            return $referer;
        }

        return $request->getSchemeAndHttpHost() . '/';
    }

    private function maintenancePage(): Response
    {
        $html = file_get_contents($this->projectDir . '/public/errors/maintenance.html');

        return new Response(
            false !== $html ? $html : 'The website is currently offline for maintenance. Please try again later.',
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['Retry-After' => '3600'],
        );
    }
}
