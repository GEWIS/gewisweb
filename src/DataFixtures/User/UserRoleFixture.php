<?php

declare(strict_types=1);

namespace App\DataFixtures\User;

use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Entity\User\UserRole;
use DateInterval;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

use function range;

class UserRoleFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Admins (8000 - 8002)
        foreach (
            range(
                8000,
                8002,
            ) as $lidnr
        ) {
            $adminRole = new UserRole();
            $adminRole->setRole(UserRoles::Admin);
            $adminRole->setExpiration(new DateTime()->add(new DateInterval('P10Y')));
            $adminRole->setLidnr($this->getReference('user-' . $lidnr, User::class));

            $manager->persist($adminRole);
        }

        // Company admins (8003 - 8004)
        foreach (
            range(
                8003,
                8004,
            ) as $lidnr
        ) {
            $companyAdminRole = new UserRole();
            $companyAdminRole->setRole(UserRoles::CompanyAdmin);
            $companyAdminRole->setExpiration(new DateTime()->add(new DateInterval('P10Y')));
            $companyAdminRole->setLidnr($this->getReference('user-' . $lidnr, User::class));

            $manager->persist($companyAdminRole);
        }

        $manager->flush();
    }

    /**
     * @return array<array-key, class-string<Fixture>>
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
