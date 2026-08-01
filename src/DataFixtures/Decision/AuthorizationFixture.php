<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Decision\Authorization;
use App\Entity\Decision\Member;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * A valid and a revoked authorization for the upcoming GMM (ALV-3), so the member page, the received count, and the
 * admin tables have data.
 */
class AuthorizationFixture extends Fixture implements DependentFixtureInterface
{
    private const int UPCOMING_GMM_NUMBER = 3;

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $valid = new Authorization();
        $valid->setAuthorizer($this->getReference(
            'member-8010',
            Member::class,
        ));
        $valid->setRecipient($this->getReference(
            'member-8005',
            Member::class,
        ));
        $valid->setMeetingNumber(self::UPCOMING_GMM_NUMBER);
        $valid->setCreatedAt(new DateTime('-3 days'));

        $manager->persist($valid);

        $revoked = new Authorization();
        $revoked->setAuthorizer($this->getReference(
            'member-8011',
            Member::class,
        ));
        $revoked->setRecipient($this->getReference(
            'member-8005',
            Member::class,
        ));
        $revoked->setMeetingNumber(self::UPCOMING_GMM_NUMBER);
        $revoked->setCreatedAt(new DateTime('-2 days'));
        $revoked->setRevokedAt(new DateTime('-1 day'));

        $manager->persist($revoked);
        $manager->flush();
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MeetingFixture::class,
            MemberFixture::class,
        ];
    }
}
