<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Decision\Address;
use App\Entity\Decision\Enums\AddressTypes;
use App\Entity\Decision\Enums\PostalRegions;
use App\Entity\Decision\Member;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Override;

use function range;
use function sprintf;
use function strtoupper;

class AddressFixture extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $this->faker = FakerFactory::create();

        foreach (
            range(
                8000,
                8199,
            ) as $lidnr
        ) {
            $member = $this->getReference(
                'member-' . $lidnr,
                Member::class,
            );

            $manager->persist($this->makeAddress($member, AddressTypes::Student));

            // Even members also get a parental home and a separate mailing address, so the profile page renders the
            // address type tabs with more than one option to switch between.
            if (0 !== $lidnr % 2) {
                continue;
            }

            $manager->persist($this->makeAddress($member, AddressTypes::Home));
            $manager->persist($this->makeAddress($member, AddressTypes::Mail));
        }

        $manager->flush();
    }

    private function makeAddress(
        Member $member,
        AddressTypes $type,
    ): Address {
        $address = new Address();
        $address->setMember($member);
        $address->setType($type);
        $address->setCountry(PostalRegions::Netherlands);
        $address->setStreet($this->faker->streetName());
        $address->setNumber((string) $this->faker->numberBetween(1, 300));
        $address->setPostalCode(sprintf(
            '%04d %s',
            $this->faker->numberBetween(
                1000,
                9999,
            ),
            strtoupper($this->faker->randomLetter() . $this->faker->randomLetter()),
        ));
        $address->setCity($this->faker->city());
        $address->setPhone($this->faker->phoneNumber());

        return $address;
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
