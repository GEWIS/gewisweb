<?php

declare(strict_types=1);

namespace App\Tests\Security\User;

use App\Entity\Application\MaintenanceWindow;
use App\Entity\User\CompanyUser;
use App\Repository\Application\MaintenanceWindowRepository;
use App\Repository\Career\CompanyPackageRepository;
use App\Security\User\UserChecker;
use App\Service\Application\MaintenanceStatusProvider;
use App\Service\User\CompanyUserAccessPolicy;
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

    public function testARepresentativeWhoseCompanyStillHasAContractMaySignIn(): void
    {
        $this->expectNotToPerformAssertions();

        $this->checker(
            null,
            ['ROLE_COMPANY_USER'],
            allowed: true,
        )->checkPreAuth(self::createStub(CompanyUser::class));
    }

    public function testARepresentativeWithoutAccessIsRefusedTheSameWayAnyoneElseIs(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);

        $this->checker(
            null,
            ['ROLE_COMPANY_USER'],
            allowed: false,
        )->checkPreAuth(self::createStub(CompanyUser::class));
    }

    /**
     * @param string[] $reachableRoles
     */
    private function checker(
        ?MaintenanceWindow $active,
        array $reachableRoles,
        bool $allowed = true,
    ): UserChecker {
        $repository = self::createStub(MaintenanceWindowRepository::class);
        $repository->method('findActiveAt')->willReturn($active);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $roleHierarchy = self::createStub(RoleHierarchyInterface::class);
        $roleHierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $companyPackages = self::createStub(CompanyPackageRepository::class);
        $companyPackages->method('hasNonExpiredPackage')->willReturn($allowed);

        return new UserChecker(
            self::createStub(TranslatorInterface::class),
            new MaintenanceStatusProvider(
                $repository,
                $requestStack,
            ),
            $roleHierarchy,
            new CompanyUserAccessPolicy($companyPackages),
        );
    }
}
