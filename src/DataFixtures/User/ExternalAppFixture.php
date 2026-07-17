<?php

declare(strict_types=1);

namespace App\DataFixtures\User;

use App\Entity\User\Enums\ExternalAppSignature;
use App\Entity\User\Enums\ExternalAppTokenDelivery;
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
        // against. It is a legacy HS512 application; the secret is development-only seed data.
        $sudosos = new ExternalApp();
        $sudosos->setAppId('sudosos');
        $sudosos->setSignature(ExternalAppSignature::HS512);
        $sudosos->setTokenDelivery(ExternalAppTokenDelivery::Query);
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

        // StarCommunity's test application. It verifies RS512, so it stays on that profile even though new
        // applications should prefer a stronger algorithm. The token is returned in the URL fragment.
        $hubble = new ExternalApp();
        $hubble->setAppId('hubble-test');
        $hubble->setSignature(ExternalAppSignature::RS512);
        $hubble->setTokenDelivery(ExternalAppTokenDelivery::Fragment);
        $hubble->setCallback('https://login.test.starcommunity.app/auth/callback/gewis/');
        $hubble->setUrl('https://test.starcommunity.app');
        $hubble->setClaims([
            JWTClaims::Name,
            JWTClaims::GivenName,
            JWTClaims::Email,
            JWTClaims::EmailVerified,
            JWTClaims::IsMember,
        ]);
        $manager->persist($hubble);

        // A test application on the recommended EdDSA profile, so the default modern path has something to exercise.
        $eddsa = new ExternalApp();
        $eddsa->setAppId('eddsa-test');
        $eddsa->setSignature(ExternalAppSignature::EdDSA);
        $eddsa->setTokenDelivery(ExternalAppTokenDelivery::Fragment);
        $eddsa->setCallback('https://eddsa.test.gewis.nl/auth/callback');
        $eddsa->setUrl('https://eddsa.test.gewis.nl');
        $eddsa->setClaims([
            JWTClaims::Name,
            JWTClaims::GivenName,
            JWTClaims::Email,
            JWTClaims::EmailVerified,
            JWTClaims::IsMember,
        ]);
        $manager->persist($eddsa);

        $manager->flush();
    }
}
