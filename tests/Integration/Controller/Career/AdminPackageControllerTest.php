<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\AdminPackageController;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyAuditLog;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyJobPackage;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Repository\Career\CompanyAuditLogRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\User\CompanyUserRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Tests\Support\UploadsBanners;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class AdminPackageControllerTest extends DatabaseTestCase
{
    use UploadsBanners;

    public function testTheOverviewNamesWhatIsAboutToRunOut(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        // The seed's packages run to 2100, so nothing expires within the horizon until one is moved.
        $banner = $this->bannerPackage();
        $banner->setExpirationDate(new DateTime('+2 weeks'));
        $this->entityManager->flush();

        $response = $this->controller()->index();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            'Orbit Analytics',
            (string) $response->getContent(),
        );
    }

    public function testApprovingAProposedBannerMakesItTheLiveOne(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $banner = $this->bannerPackage();
        $wasLive = $banner->getImage();
        $banner->proposeImage(
            'career/2/images/ab/proposed.png',
            $this->representative(),
        );
        $this->entityManager->flush();

        $this->controller()->approveBanner(
            $banner,
            $this->user(),
        );

        self::assertSame(
            'career/2/images/ab/proposed.png',
            $banner->getImage(),
        );
        self::assertNotSame(
            $wasLive,
            $banner->getImage(),
        );
        self::assertFalse($banner->hasPendingImage());
        self::assertSame(
            CompanyAuditVerbs::BannerApproved,
            $this->timeline($banner->getCompany())[0]->getVerb(),
        );
    }

    public function testRejectingAProposedBannerLeavesTheLiveOneAlone(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $banner = $this->bannerPackage();
        $wasLive = $banner->getImage();
        $banner->proposeImage(
            'career/2/images/cd/proposed.png',
            $this->representative(),
        );
        $this->entityManager->flush();

        $this->controller()->rejectBanner(
            $banner,
            $this->user(),
        );

        self::assertSame(
            $wasLive,
            $banner->getImage(),
        );
        self::assertFalse($banner->hasPendingImage());
        self::assertSame(
            CompanyAuditVerbs::BannerRejected,
            $this->timeline($banner->getCompany())[0]->getVerb(),
        );
    }

    /**
     * The vacancies of a job package hang off it by a Doctrine cascade, but their revisions, the review threads on
     * those revisions and the highlight picks pointing at them do not, so this is the one delete that has a whole
     * chain behind it.
     */
    public function testRemovingAJobPackageTakesItsVacanciesAndTheirRevisionsWithIt(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $package = $this->jobPackage();
        $company = $package->getCompany();

        $vacancyIds = [];
        $revisionIds = [];
        foreach ($package->getVacancies() as $vacancy) {
            $vacancyIds[] = $vacancy->getId();

            foreach ($vacancy->getRevisions() as $revision) {
                $revisionIds[] = $revision->getId();
            }
        }

        self::assertNotEmpty($vacancyIds);
        self::assertNotEmpty($revisionIds);

        $this->controller()->delete(
            $package,
            $this->user(),
        );

        self::assertEmpty($this->entityManager->getRepository(Vacancy::class)->findBy(['id' => $vacancyIds]));
        self::assertEmpty($this->entityManager->getRepository(VacancyRevision::class)->findBy(['id' => $revisionIds]));
        self::assertSame(
            CompanyAuditVerbs::PackageDeleted,
            $this->timeline($company)[0]->getVerb(),
        );
    }

    /**
     * A company that emails its artwork to the committee instead of uploading it should not be stuck waiting for a
     * representative account, so the committee puts the banner up itself and it is live at once.
     */
    public function testTheCommitteeCanPutABannerUpItself(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $banner = $this->bannerPackage();
        $wasLive = $banner->getImage();

        $this->controller()->banner(
            $this->bannerUploadRequest($banner->getFormat()),
            $banner,
            $this->user(),
        );

        self::assertNotSame(
            $wasLive,
            $banner->getImage(),
        );
        self::assertSame(
            CompanyAuditVerbs::BannerReplaced,
            $this->timeline($banner->getCompany())[0]->getVerb(),
        );
    }

    public function testTheBannerPageShowsWhatIsWaitingForADecision(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $response = $this->controller()->banner(
            new Request(),
            $this->bannerPackage(),
            $this->user(),
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            'Waiting for a decision',
            (string) $response->getContent(),
        );
    }

    public function testOnlyABannerPackageHasABannerPage(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $this->expectException(NotFoundHttpException::class);
        $this->controller()->banner(
            new Request(),
            $this->jobPackage(),
            $this->user(),
        );
    }

    public function testThereIsNothingToDecideOnABannerWithoutAProposal(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        // The seed leaves a proposal waiting, so clear it before asking about a banner that has none.
        $banner = $this->bannerPackage();
        $banner->rejectPendingImage();
        $this->entityManager->flush();

        $this->expectException(NotFoundHttpException::class);
        $this->controller()->approveBanner(
            $banner,
            $this->user(),
        );
    }

    /**
     * @return list<CompanyAuditLog>
     */
    private function timeline(Company $company): array
    {
        return self::getContainer()->get(CompanyAuditLogRepository::class)->findRecentForCompany($company);
    }

    private function bannerPackage(): CompanyBannerPackage
    {
        foreach ($this->company('orbit-analytics')->getPackages() as $package) {
            if (!$package instanceof CompanyBannerPackage) {
                continue;
            }

            return $package;
        }

        self::fail('The seed is expected to give Orbit Analytics a banner package.');
    }

    private function jobPackage(): CompanyJobPackage
    {
        foreach ($this->company('orbit-analytics')->getPackages() as $package) {
            if (
                !$package instanceof CompanyJobPackage
                || $package->getVacancies()->isEmpty()
            ) {
                continue;
            }

            return $package;
        }

        self::fail('The seed is expected to give Orbit Analytics a job package with vacancies.');
    }

    private function representative(): CompanyUser
    {
        $companyUser = self::getContainer()->get(CompanyUserRepository::class)
            ->loadUserByIdentifier('recruitment@orbit-analytics.example.com');
        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );

        return $companyUser;
    }

    private function company(string $slug): Company
    {
        $company = self::getContainer()->get(CompanyRepository::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        return $company;
    }

    private function controller(): AdminPackageController
    {
        return self::getContainer()->get(AdminPackageController::class);
    }

    private function authenticate(int $lidnr = 8025): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->user($lidnr),
            'main',
            [
                'ROLE_COMPANY_ADMIN',
                'ROLE_BOARD',
            ],
        ));
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

    private function user(int $lidnr = 8025): User
    {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }
}
