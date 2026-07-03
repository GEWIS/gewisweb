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
            // == gewiswebgewis. The cost (argon2id m=10, t=3) matches the configured hasher in dev and test
            // (config/packages/security.yaml), so logging in as a seeded user triggers no rehash-on-login UPDATE.
            $user->setPassword(
                '$argon2id$v=19$m=10,t=3,p=1$8fI5jXSYT4a/nmlANyW5iw$1eFNdB11zahtXd/ooeCWprWuCvAGDx+OrUsH2lBZNVM',
            );
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
