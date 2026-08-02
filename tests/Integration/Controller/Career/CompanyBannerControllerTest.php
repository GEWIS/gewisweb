<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\AdminPackageController;
use App\Controller\Career\CompanyBannerController;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\Enums\CompanyBannerFormats;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Repository\User\CompanyUserRepository;
use App\Service\Application\FileStorage;
use App\Tests\Integration\DatabaseTestCase;
use App\Tests\Support\UploadsBanners;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class CompanyBannerControllerTest extends DatabaseTestCase
{
    use UploadsBanners;

    public function testProposingStoresTheImageAndLeavesTheLiveBannerAlone(): void
    {
        $companyUser = $this->signIn();
        $package = $this->bannerPackage();
        $wasLive = $package->getImage();

        $this->controller()->index(
            $this->bannerUploadRequest($package->getFormat()),
            $companyUser,
        );

        self::assertTrue($package->hasPendingImage());
        self::assertSame(
            $wasLive,
            $package->getImage(),
        );
        self::assertSame(
            $companyUser,
            $package->getPendingImageSubmittedBy(),
        );

        $pending = $package->getPendingImage();
        self::assertIsString($pending);
        self::assertTrue(self::getContainer()->get(FileStorage::class)->exists($pending));
    }

    /**
     * A banner is artwork made to a size, so one that is not that size is refused rather than cropped towards it.
     */
    public function testAnImageThatIsNotTheFormatTheyBoughtIsRefused(): void
    {
        $companyUser = $this->signIn();
        $package = $this->bannerPackage();

        // The seed leaves a proposal waiting, so clear it before asking whether a refused one puts anything there.
        $package->rejectPendingImage();

        $this->controller()->index(
            $this->bannerUploadRequest(CompanyBannerFormats::Billboard),
            $companyUser,
        );

        self::assertFalse($package->hasPendingImage());
    }

    public function testTheCommitteeApprovingItMakesItLive(): void
    {
        $companyUser = $this->signIn();
        $package = $this->bannerPackage();
        $this->controller()->index(
            $this->bannerUploadRequest($package->getFormat()),
            $companyUser,
        );
        $proposed = $package->getPendingImage();

        $this->authenticateBoard();
        self::getContainer()->get(AdminPackageController::class)->approveBanner(
            $package,
            $this->boardMember(),
        );

        self::assertSame(
            $proposed,
            $package->getImage(),
        );
        self::assertFalse($package->hasPendingImage());
    }

    /**
     * Nothing points at a rejected proposal afterwards, so its bytes are reclaimed.
     */
    public function testTheCommitteeRejectingItReclaimsTheFile(): void
    {
        $companyUser = $this->signIn();
        $package = $this->bannerPackage();
        $this->controller()->index(
            $this->bannerUploadRequest($package->getFormat()),
            $companyUser,
        );
        $proposed = $package->getPendingImage();
        self::assertIsString($proposed);

        $this->authenticateBoard();
        self::getContainer()->get(AdminPackageController::class)->rejectBanner(
            $package,
            $this->boardMember(),
        );

        self::assertFalse($package->hasPendingImage());
        self::assertFalse(self::getContainer()->get(FileStorage::class)->exists($proposed));
    }

    public function testACompanyWithoutABannerPackageIsToldWhereToAsk(): void
    {
        $companyUser = $this->signIn('recruitment@nexunt.example.com');

        $response = $this->controller()->index(
            new Request(),
            $companyUser,
        );

        self::assertStringContainsString(
            'do not have a banner package',
            (string) $response->getContent(),
        );
    }

    private function bannerPackage(): CompanyBannerPackage
    {
        $companyUser = self::getContainer()->get(CompanyUserRepository::class)
            ->loadUserByIdentifier('recruitment@orbit-analytics.example.com');
        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );

        foreach ($companyUser->getCompany()->getPackages() as $package) {
            if (!$package instanceof CompanyBannerPackage) {
                continue;
            }

            return $package;
        }

        self::fail('The seed is expected to give Orbit Analytics a banner package.');
    }

    private function signIn(string $email = 'recruitment@orbit-analytics.example.com'): CompanyUser
    {
        $companyUser = self::getContainer()->get(CompanyUserRepository::class)->loadUserByIdentifier($email);
        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $companyUser,
            'company',
            ['ROLE_COMPANY_USER'],
        ));
        $this->pushRequestWithSession();

        return $companyUser;
    }

    private function authenticateBoard(): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->boardMember(),
            'main',
            [
                'ROLE_COMPANY_ADMIN',
                'ROLE_BOARD',
            ],
        ));
    }

    private function boardMember(): User
    {
        $user = $this->entityManager->getRepository(User::class)->find(8025);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }

    private function pushRequestWithSession(): FlashBagAwareSessionInterface
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);

        return $session;
    }

    private function controller(): CompanyBannerController
    {
        return self::getContainer()->get(CompanyBannerController::class);
    }
}
