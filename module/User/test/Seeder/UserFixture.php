<?php

declare(strict_types=1);

namespace UserTest\Seeder;

use DateTime;
use Decision\Model\Member;
use DecisionTest\Seeder\MemberFixture;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use User\Model\User;

use function range;

class UserFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (range(8000, 8199) as $lidnr) {
            $user = new User();
            $user->setLidnr($lidnr);
            $user->setMember($this->getReference('member-' . $lidnr, Member::class));
            $user->setPassword('$2y$13$j.ggomvkEeev1tcrsg7tEObJdD0LGQpmfT/4k8zwclvyFM5zFxkde'); // == gewiswebgewis
            $user->setPasswordChangedOn(new DateTime());

            $manager->persist($user);
            $this->addReference('user-' . $lidnr, $user);
        }

        $manager->flush();
    }

    /**
     * @return class-string[]
     */
    public function getDependencies(): array
    {
        return [
            MemberFixture::class,
        ];
    }
}
