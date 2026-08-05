<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\AdminRepresentativeController;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyAuditLog;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Repository\Career\CompanyAuditLogRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\User\CompanyUserRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The actions are invoked directly with the current user set on the token storage, rather than over HTTP, as the rest
 * of the controller suite does: a synthetic browser session does not survive the app's session guard.
 */
final class AdminRepresentativeControllerTest extends DatabaseTestCase
{
    public function testTheOverviewListsEverybodyIncludingWhoeverHasMovedOn(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $response = $this->controller()->index(
            $this->companyId(),
            new Request(),
            $this->user(),
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        $content = (string) $response->getContent();
        self::assertStringContainsString(
            'recruitment@nexunt.example.com',
            $content,
        );
        self::assertStringContainsString(
            'former@nexunt.example.com',
            $content,
        );
    }

    public function testShuttingSomebodyOutRecordsWhenAndSaysSoOnTheTimeline(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $representative = $this->representative('talent@nexunt.example.com');
        $response = $this->controller()->disableRepresentative(
            $this->companyId(),
            (int) $representative->getId(),
            $this->user(),
        );

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertTrue($representative->isDisabled());
        self::assertSame(
            CompanyAuditVerbs::RepresentativeDisabled,
            $this->timeline()[0]->getVerb(),
        );
    }

    public function testLettingSomebodyBackInClearsThat(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $representative = $this->representative('former@nexunt.example.com');
        $this->controller()->enableRepresentative(
            $this->companyId(),
            (int) $representative->getId(),
            $this->user(),
        );

        self::assertFalse($representative->isDisabled());
        self::assertSame(
            CompanyAuditVerbs::RepresentativeEnabled,
            $this->timeline()[0]->getVerb(),
        );
    }

    /**
     * The board writes to the primary contact and expects an answer, which somebody who cannot sign in will not give.
     */
    public function testSomebodyWhoCannotSignInCannotBecomeThePrimaryContact(): void
    {
        $this->authenticate();
        $session = $this->pushRequestWithSession();

        $company = $this->company();
        $before = $company->getPrimaryContact();

        $this->controller()->makePrimaryContact(
            (int) $company->getId(),
            (int) $this->representative('former@nexunt.example.com')->getId(),
            $this->user(),
        );

        self::assertSame(
            $before,
            $company->getPrimaryContact(),
        );
        self::assertNotEmpty($session->getFlashBag()->peek('warning'));
    }

    public function testAppointingAPrimaryContactSwapsItOver(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $company = $this->company();
        $representative = $this->representative('talent@nexunt.example.com');

        $this->controller()->makePrimaryContact(
            (int) $company->getId(),
            (int) $representative->getId(),
            $this->user(),
        );

        self::assertSame(
            $representative,
            $company->getPrimaryContact(),
        );
        self::assertSame(
            CompanyAuditVerbs::PrimaryContactChanged,
            $this->timeline()[0]->getVerb(),
        );
    }

    public function testRemovingDeletesTheAccountAndLeavesTheCompanyWithoutAContact(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $company = $this->company();
        $primary = $company->getPrimaryContact();
        self::assertInstanceOf(
            CompanyUser::class,
            $primary,
        );
        $representativeId = (int) $primary->getId();

        $this->controller()->removeRepresentative(
            (int) $company->getId(),
            $representativeId,
            $this->user(),
        );

        $this->entityManager->refresh($company);

        self::assertNull($this->companyUsers()->find($representativeId));
        self::assertNull($company->getPrimaryContact());
        self::assertSame(
            CompanyAuditVerbs::RepresentativeRemoved,
            $this->timeline()[0]->getVerb(),
        );
    }

    public function testARepresentativeOfAnotherCompanyIsNotReachableThroughThisOne(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $this->expectException(NotFoundHttpException::class);
        $this->controller()->disableRepresentative(
            $this->companyId(),
            (int) $this->representative('recruitment@orbit-analytics.example.com')->getId(),
            $this->user(),
        );
    }

    /**
     * @return list<CompanyAuditLog>
     */
    private function timeline(): array
    {
        return self::getContainer()->get(CompanyAuditLogRepository::class)->findRecentForCompany($this->company());
    }

    private function controller(): AdminRepresentativeController
    {
        return self::getContainer()->get(AdminRepresentativeController::class);
    }

    private function companyId(): int
    {
        return (int) $this->company()->getId();
    }

    private function company(string $slug = 'nexunt'): Company
    {
        $company = self::getContainer()->get(CompanyRepository::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        return $company;
    }

    private function representative(string $email): CompanyUser
    {
        $companyUser = $this->companyUsers()->loadUserByIdentifier($email);
        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );

        return $companyUser;
    }

    private function companyUsers(): CompanyUserRepository
    {
        return self::getContainer()->get(CompanyUserRepository::class);
    }

    private function authenticate(int $lidnr = 8025): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->user($lidnr),
            'main',
            ['ROLE_COMPANY_ADMIN'],
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
