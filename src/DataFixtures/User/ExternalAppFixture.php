<?php

declare(strict_types=1);

namespace App\DataFixtures\User;

use App\Entity\User\Enums\JWTClaims;
use App\Entity\User\ExternalApp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

class ExternalAppFixture extends Fixture
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // SudoSOS is linked from the members dropdown in the navbar, so it needs a registered app to authenticate
        // against. The secret is development-only seed data.
        $sudosos = new ExternalApp();
        $sudosos->setAppId('sudosos');
        $sudosos->setSecret('sudosos-development-secret-0123456789abcdef0123456789abcdef0123456789');
        $sudosos->setCallback('https://sudosos.test.gewis.nl/token');
        $sudosos->setUrl('https://sudosos.test.gewis.nl');
        $sudosos->setClaims([
            JWTClaims::Lidnr,
            JWTClaims::GivenName,
            JWTClaims::FamilyName,
            JWTClaims::Email,
        ]);

        $manager->persist($sudosos);
        $manager->flush();
    }
}
