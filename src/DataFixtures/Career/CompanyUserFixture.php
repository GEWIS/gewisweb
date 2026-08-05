<?php

declare(strict_types=1);

namespace App\DataFixtures\Career;

use App\Entity\Career\Company;
use App\Entity\User\CompanyUser;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * Seeds the representatives who sign in to the careers portal. Nexunt gets three of them so the multi-representative
 * surfaces have something to show, one of whom has moved on and can no longer sign in; Halcyon's contract has lapsed,
 * so its representative is shut out for the other reason.
 */
class CompanyUserFixture extends Fixture implements DependentFixtureInterface
{
    /**
     * == gewiswebgewis. The cost (argon2id m=10, t=3) matches the configured hasher in dev and test
     * (config/packages/security.yaml), so signing in as a seeded representative triggers no rehash-on-login UPDATE.
     */
    private const string PASSWORD_HASH =
        '$argon2id$v=19$m=10,t=3,p=1$8fI5jXSYT4a/nmlANyW5iw$1eFNdB11zahtXd/ooeCWprWuCvAGDx+OrUsH2lBZNVM';

    private const array REPRESENTATIVES = [
        'nexunt' => [
            [
                'email' => 'recruitment@nexunt.example.com',
                'name' => 'Ilse Vermeer',
            ],
            [
                'email' => 'talent@nexunt.example.com',
                'name' => 'Bram de Wit',
            ],
            [
                'email' => 'former@nexunt.example.com',
                'name' => 'Joris Peeters',
                'disabled' => true,
            ],
        ],
        'orbit-analytics' => [
            [
                'email' => 'recruitment@orbit-analytics.example.com',
                'name' => 'Noor El Amrani',
            ],
        ],
        'delta-robotics' => [
            [
                'email' => 'recruitment@delta-robotics.example.com',
                'name' => 'Sander Kuipers',
            ],
        ],
        'halcyon-mobility' => [
            [
                'email' => 'recruitment@halcyon-mobility.example.com',
                'name' => 'Fenna Bakker',
            ],
        ],
    ];

    #[Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::REPRESENTATIVES as $slug => $representatives) {
            $company = $this->getReference(
                'career-company-' . $slug,
                Company::class,
            );

            foreach ($representatives as $index => $representative) {
                $companyUser = new CompanyUser();
                $companyUser->setCompany($company);
                $companyUser->setName($representative['name']);
                $companyUser->setEmail($representative['email']);
                $companyUser->setPassword(self::PASSWORD_HASH);
                $companyUser->setPasswordChangedOn(new DateTime());

                if ($representative['disabled'] ?? false) {
                    $companyUser->setDisabledAt(new DateTime('2025-11-01'));
                }

                if (0 === $index) {
                    $company->setPrimaryContact($companyUser);
                }

                $manager->persist($companyUser);
                $this->addReference(
                    'career-company-user-' . $representative['email'],
                    $companyUser,
                );
            }
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
            CompanyFixture::class,
        ];
    }
}
