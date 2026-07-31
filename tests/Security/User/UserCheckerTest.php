<?php

declare(strict_types=1);

namespace App\Tests\Security\User;

use App\Entity\Application\MaintenanceWindow;
use App\Repository\Application\MaintenanceWindowRepository;
use App\Security\User\UserChecker;
use App\Service\Application\MaintenanceStatusProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserCheckerTest extends TestCase
{
    public function testSignInIsAllowedWhenNoMaintenanceIsActive(): void
    {
        $this->expectNotToPerformAssertions();

        $this->checker(
            null,
            ['ROLE_USER'],
        )->checkPostAuth(self::createStub(UserInterface::class));
    }

    public function testAdminsMaySignInDuringMaintenance(): void
    {
        $this->expectNotToPerformAssertions();

        $this->checker(
            new MaintenanceWindow(),
            [
                'ROLE_ADMIN',
                'ROLE_USER',
            ],
        )->checkPostAuth(self::createStub(UserInterface::class));
    }

    public function testNonAdminsCannotSignInDuringMaintenance(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);

        $this->checker(
            new MaintenanceWindow(),
            ['ROLE_USER'],
        )->checkPostAuth(self::createStub(UserInterface::class));
    }

    /**
     * @param string[] $reachableRoles
     */
    private function checker(
        ?MaintenanceWindow $active,
        array $reachableRoles,
    ): UserChecker {
        $repository = self::createStub(MaintenanceWindowRepository::class);
        $repository->method('findActiveAt')->willReturn($active);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $roleHierarchy = self::createStub(RoleHierarchyInterface::class);
        $roleHierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        return new UserChecker(
            self::createStub(TranslatorInterface::class),
            new MaintenanceStatusProvider(
                $repository,
                $requestStack,
            ),
            $roleHierarchy,
        );
    }
}
