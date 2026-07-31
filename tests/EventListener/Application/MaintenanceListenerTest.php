<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Application;

use App\Entity\Application\Enums\MaintenanceStatus;
use App\Entity\Application\MaintenanceWindow;
use App\EventListener\Application\MaintenanceListener;
use App\Repository\Application\MaintenanceWindowRepository;
use App\Service\Application\MaintenanceStatusProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function dirname;

final class MaintenanceListenerTest extends TestCase
{
    public function testTheEnvironmentFlagServesTheMaintenancePageToEveryone(): void
    {
        $event = $this->event(Request::create('/en/'));
        $this->listener(
            $this->provider(null),
            false,
            true,
        )($event);

        self::assertSame(
            Response::HTTP_SERVICE_UNAVAILABLE,
            $event->getResponse()?->getStatusCode(),
        );
    }

    public function testFullMaintenanceLeavesTheSignInFlowReachable(): void
    {
        $request = Request::create('/en/login');
        $request->attributes->set(
            '_route',
            'user_login',
        );

        $event = $this->event($request);
        $this->listener(
            $this->provider($this->window(MaintenanceStatus::Full)),
            false,
        )($event);

        self::assertNull($event->getResponse());
    }

    public function testWithoutAnActiveWindowTheRequestPassesThrough(): void
    {
        $event = $this->event(Request::create('/en/'));
        $this->listener(
            $this->provider(null),
            false,
        )($event);

        self::assertNull($event->getResponse());
    }

    public function testAdminsBypassMaintenance(): void
    {
        $event = $this->event(Request::create('/en/', 'POST'));
        $this->listener(
            $this->provider($this->window(MaintenanceStatus::Full)),
            true,
        )($event);

        self::assertNull($event->getResponse());
    }

    public function testFullMaintenanceServesTheMaintenancePage(): void
    {
        $event = $this->event(Request::create('/en/'));
        $this->listener(
            $this->provider($this->window(MaintenanceStatus::Full)),
            false,
        )($event);

        self::assertSame(
            Response::HTTP_SERVICE_UNAVAILABLE,
            $event->getResponse()?->getStatusCode(),
        );
    }

    public function testReadOnlyLetsReadsThrough(): void
    {
        $event = $this->event(Request::create('/en/'));
        $this->listener(
            $this->provider($this->window(MaintenanceStatus::ReadOnly)),
            false,
        )($event);

        self::assertNull($event->getResponse());
    }

    public function testReadOnlyBouncesAWriteBackToThePreviousPage(): void
    {
        $request = Request::create(
            '/en/',
            'POST',
        );
        $request->headers->set(
            'referer',
            'http://localhost/en/photo',
        );
        $request->setSession(new Session(new MockArraySessionStorage()));

        $event = $this->event($request);
        $this->listener(
            $this->provider($this->window(MaintenanceStatus::ReadOnly)),
            false,
        )($event);

        $response = $event->getResponse();
        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertSame(
            Response::HTTP_SEE_OTHER,
            $response->getStatusCode(),
        );
        self::assertSame(
            'http://localhost/en/photo',
            $response->getTargetUrl(),
        );
    }

    private function listener(
        MaintenanceStatusProvider $maintenanceStatus,
        bool $isAdmin,
        bool $environmentFlag = false,
    ): MaintenanceListener {
        $security = self::createStub(Security::class);
        $security->method('isGranted')->willReturn($isAdmin);

        return new MaintenanceListener(
            $maintenanceStatus,
            $security,
            self::createStub(TranslatorInterface::class),
            $environmentFlag,
            dirname(
                __DIR__,
                3,
            ),
        );
    }

    private function provider(?MaintenanceWindow $active): MaintenanceStatusProvider
    {
        $repository = self::createStub(MaintenanceWindowRepository::class);
        $repository->method('findActiveAt')->willReturn($active);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        return new MaintenanceStatusProvider(
            $repository,
            $requestStack,
        );
    }

    private function window(MaintenanceStatus $status): MaintenanceWindow
    {
        $window = new MaintenanceWindow();
        $window->setStatus($status);

        return $window;
    }

    private function event(Request $request): RequestEvent
    {
        return new RequestEvent(
            self::createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
