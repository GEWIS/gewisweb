<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\CompanyController;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CompanyRevision;
use App\Entity\User\CompanyUser;
use App\Repository\User\CompanyUserRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Workflow\RevisionClonerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class CompanyControllerTest extends DatabaseTestCase
{
    public function testTheDashboardShowsWhatTheCompanysVacanciesAreDoing(): void
    {
        $content = $this->dashboard('recruitment@nexunt.example.com');

        self::assertStringContainsString(
            'Nexunt Systems',
            $content,
        );
        self::assertStringContainsString(
            'Backend Engineer',
            $content,
        );
        // The vacancy whose window closed is not live, but it is still the company's, so it is not simply hidden.
        self::assertStringContainsString(
            'Live vacancies',
            $content,
        );
    }

    public function testAProfileThatCameBackWithChangesRequestedIsCalledOut(): void
    {
        $companyUser = $this->representative('recruitment@nexunt.example.com');
        $company = $companyUser->getCompany();
        $current = $company->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $current,
        );

        // Asking for changes leaves that revision behind as a record and hands the company a fresh draft off it, so
        // the state the dashboard actually meets is the draft, not the request.
        $current->setStatus(RevisionStatus::ChangesRequested);
        $draft = self::getContainer()->get(RevisionClonerRegistry::class)->cloneAsDraft($current);
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        self::assertStringContainsString(
            'asked for changes',
            $this->dashboard('recruitment@nexunt.example.com'),
        );
    }

    public function testACompanyThatHasNeverBeenApprovedIsToldSo(): void
    {
        $companyUser = $this->representative('recruitment@nexunt.example.com');
        $companyUser->getCompany()->setLiveRevision(null);
        $this->entityManager->flush();

        self::assertStringContainsString(
            'not been approved yet',
            $this->dashboard('recruitment@nexunt.example.com'),
        );
    }

    /**
     * A company sees who else acts for it, but not the ones who have been shut out.
     */
    public function testTheRepresentativeListLeavesOutWhoeverHasMovedOn(): void
    {
        $companyUser = $this->representative('recruitment@nexunt.example.com');
        $this->authenticate($companyUser);
        $response = $this->controller()->representatives($companyUser);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        $content = (string) $response->getContent();
        self::assertStringContainsString(
            'Bram de Wit',
            $content,
        );
        self::assertStringNotContainsString(
            'Joris Peeters',
            $content,
        );
    }

    /**
     * The dashboard is built from the signed-in representative's own company, so it can never show another's.
     */
    public function testARepresentativeOnlySeesItsOwnCompany(): void
    {
        $content = $this->dashboard('recruitment@orbit-analytics.example.com');

        self::assertStringContainsString(
            'Orbit Analytics',
            $content,
        );
        self::assertStringNotContainsString(
            'Backend Engineer',
            $content,
        );
    }

    private function dashboard(string $email): string
    {
        $companyUser = $this->representative($email);
        $this->authenticate($companyUser);

        $response = $this->controller()->index($companyUser);
        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        return (string) $response->getContent();
    }

    private function representative(string $email): CompanyUser
    {
        $companyUser = self::getContainer()->get(CompanyUserRepository::class)->loadUserByIdentifier($email);
        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );

        return $companyUser;
    }

    private function authenticate(CompanyUser $companyUser): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $companyUser,
            'company',
            ['ROLE_COMPANY_USER'],
        ));

        // Rendering reads the request locale, so the stack cannot be empty.
        $session = self::getContainer()->get('session.factory')->createSession();
        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
    }

    private function controller(): CompanyController
    {
        return self::getContainer()->get(CompanyController::class);
    }
}
