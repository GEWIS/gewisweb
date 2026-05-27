<?php

declare(strict_types=1);

namespace App\DataFixtures\User;

use App\DataFixtures\Decision\MemberFixture;
use App\Entity\Decision\Member;
use App\Entity\User\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

use function range;

class UserFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        foreach (
            range(
                8000,
                8199,
            ) as $lidnr
        ) {
            $user = new User();
            $user->setLidnr($lidnr);
            $user->setMember($this->getReference('member-' . $lidnr, Member::class));
            $user->setPassword(
                '$argon2id$v=19$m=16,t=1,p=1$Z1hyaHhMLlUxdG5MeWxIcg$TzJS0e00UUpxwXV00wUhTb5G1ds73aevtDaTL6/KFbs',
            ); // == gewiswebgewis
            $user->setPasswordChangedOn(new DateTime());

            $manager->persist($user);
            $this->addReference(
                'user-' . $lidnr,
                $user,
            );
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
            MemberFixture::class,
        ];
    }
}
