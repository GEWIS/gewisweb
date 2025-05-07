<?php

declare(strict_types=1);

namespace DecisionTest\Seeder;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Decision\Model\AssociationYear;
use Decision\Model\Enums\MembershipTypes;
use Decision\Model\Member;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Override;

use function array_map;
use function explode;
use function implode;
use function intval;
use function mb_substr;
use function range;

class MemberFixture extends AbstractFixture
{
    private Generator $faker;
    private DateTimeImmutable $now;

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $this->faker = FakerFactory::create();
        $this->now = new DateTimeImmutable();

        // Admins (8000 - 8002)
        foreach (range(8000, 8002) as $lidnr) {
            $admin = new Member();
            $admin->setLidnr($lidnr);
            $admin->setFirstName('ÅDMIN');

            $admin = $this->setOtherMemberProperties(
                $admin,
                MembershipTypes::Ordinary,
                false,
                false,
                false,
            );

            $manager->persist($admin);
            $this->addReference('member-' . $lidnr, $admin);
        }

        $manager->flush();

        // Company admins (8003 - 8004)
        foreach (range(8003, 8004) as $lidnr) {
            $companyAdmin = new Member();
            $companyAdmin->setLidnr($lidnr);
            $companyAdmin->setFirstName('COMPANY_ADMIN');

            $companyAdmin = $this->setOtherMemberProperties(
                $companyAdmin,
                MembershipTypes::Ordinary,
                false,
                false,
                false,
            );

            $manager->persist($companyAdmin);
            $this->addReference('member-' . $lidnr, $companyAdmin);
        }

        $manager->flush();

        // Active (ordinary) members (8005 - 8014)
        foreach (range(8005, 8014) as $lidnr) {
            $member = new Member();
            $member->setLidnr($lidnr);
            $member->setFirstName('ORGAN_ORDINARY');

            $member = $this->setOtherMemberProperties(
                $member,
                MembershipTypes::Ordinary,
                false,
                false,
                false,
            );

            $manager->persist($member);
            $this->addReference('member-' . $lidnr, $member);
        }

        $manager->flush();

        // Active (external) members (8015 - 8019)
        foreach (range(8015, 8019) as $lidnr) {
            $member = new Member();
            $member->setLidnr($lidnr);
            $member->setFirstName('ORGAN_EXTERNAL');

            $member = $this->setOtherMemberProperties(
                $member,
                MembershipTypes::External,
                false,
                false,
                false,
            );

            $manager->persist($member);
            $this->addReference('member-' . $lidnr, $member);
        }

        $manager->flush();

        // Discharged active members (8020 - 8024)
        foreach (range(8020, 8024) as $lidnr) {
            $member = new Member();
            $member->setLidnr($lidnr);
            $member->setFirstName('ORGAN_DISCHARGED');

            $member = $this->setOtherMemberProperties(
                $member,
                MembershipTypes::Ordinary,
                false,
                false,
                false,
            );

            $manager->persist($member);
            $this->addReference('member-' . $lidnr, $member);
        }

        $manager->flush();

        // Ordinary members (8025 - 8124)
        foreach (range(8025, 8124) as $lidnr) {
            $member = new Member();
            $member->setLidnr($lidnr);
            $member->setFirstName($this->faker->firstName());

            $member = $this->setOtherMemberProperties(
                $member,
                MembershipTypes::Ordinary,
                false,
                false,
                false,
            );

            $manager->persist($member);
            $this->addReference('member-' . $lidnr, $member);
        }

        $manager->flush();

        // External members (8125 - 8149)
        foreach (range(8125, 8149) as $lidnr) {
            $external = new Member();
            $external->setLidnr($lidnr);
            $external->setFirstName($this->faker->firstName());

            $external = $this->setOtherMemberProperties(
                $external,
                MembershipTypes::External,
                false,
                false,
                false,
            );

            $manager->persist($external);
            $this->addReference('member-' . $lidnr, $external);
        }

        $manager->flush();

        // Honorary members (8150 - 8154)
        foreach (range(8150, 8154) as $lidnr) {
            $honorary = new Member();
            $honorary->setLidnr($lidnr);
            $honorary->setFirstName($this->faker->firstName());

            $honorary = $this->setOtherMemberProperties(
                $honorary,
                MembershipTypes::Honorary,
                false,
                false,
                false,
            );

            $manager->persist($honorary);
            $this->addReference('member-' . $lidnr, $honorary);
        }

        $manager->flush();

        // Graduates (8155 - 8199)
        foreach (range(8155, 8199) as $lidnr) {
            $graduate = new Member();
            $graduate->setLidnr($lidnr);
            $graduate->setFirstName($this->faker->firstName());

            $graduate = $this->setOtherMemberProperties(
                $graduate,
                MembershipTypes::Graduate,
                $this->faker->boolean(),
                false,
                false,
            );

            $manager->persist($graduate);
            $this->addReference('member-' . $lidnr, $graduate);
        }

        $manager->flush();
    }

    private function setOtherMemberProperties(
        Member $member,
        MembershipTypes $membershipType,
        bool $expired,
        bool $hidden = false,
        bool $deleted = false,
    ): Member {
        $member->setInitials(
            implode(
                '.',
                array_map(
                    static function ($name) {
                        return mb_substr($name, 0, 1);
                    },
                    explode(
                        ' ',
                        $member->getFirstName(),
                    ),
                ),
            ) . '.',
        );
        $member->setMiddleName('');
        $member->setLastName($this->faker->lastName());

        $member->setEmail($this->faker->email());
        $member->setBirth($this->faker->dateTimeThisCentury('-16 years'));

        $member->setGeneration(intval($this->faker->year()));
        $member->setType($membershipType);

        if (!$expired) {
            // If not expired, expire next year.
            $member->setExpiration(
                AssociationYear::fromDate(
                    DateTime::createFromImmutable($this->now->add(new DateInterval('P1Y'))),
                )->getStartDate(),
            );

            if (MembershipTypes::Ordinary === $membershipType) {
                $member->setMembershipEndsOn(null);
            } else {
                $member->setMembershipEndsOn($member->getExpiration());
            }
        } else {
            $member->setExpiration(
                AssociationYear::fromDate(
                    DateTime::createFromImmutable($this->now->sub(new DateInterval('P2Y'))),
                )->getStartDate(),
            );
            $member->setMembershipEndsOn($member->getExpiration()->sub(new DateInterval('P1Y')));
        }

        $member->setHidden($hidden);
        $member->setDeleted($deleted);
        $member->setAuthenticationKey($expired ? null : $this->faker->sha256());
        $member->setChangedOn(DateTime::createFromImmutable($this->now));

        return $member;
    }
}
