<?php

declare(strict_types=1);

namespace UserTest\Seeder;

use DateInterval;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use User\Model\Enums\UserRoles;
use User\Model\User;
use User\Model\UserRole;

use function range;

class UserRoleFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Admins (8000 - 8002)
        foreach (range(8000, 8002) as $lidnr) {
            $adminRole = new UserRole();
            $adminRole->setRole(UserRoles::Admin);
            $adminRole->setExpiration((new DateTime())->add(new DateInterval('P10Y')));
            $adminRole->setLidnr($this->getReference('user-' . $lidnr, User::class));

            $manager->persist($adminRole);
        }

        // Company admins (8003 - 8004)
        foreach (range(8003, 8004) as $lidnr) {
            $companyAdminRole = new UserRole();
            $companyAdminRole->setRole(UserRoles::CompanyAdmin);
            $companyAdminRole->setExpiration((new DateTime())->add(new DateInterval('P10Y')));
            $companyAdminRole->setLidnr($this->getReference('user-' . $lidnr, User::class));

            $manager->persist($companyAdminRole);
        }

        $manager->flush();
    }

    /**
     * @return class-string[]
     */
    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
