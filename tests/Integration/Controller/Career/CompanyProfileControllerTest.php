<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\CompanyProfileController;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CompanyRevision;
use App\Entity\User\CompanyUser;
use App\Repository\User\CompanyUserRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class CompanyProfileControllerTest extends DatabaseTestCase
{
    public function testTheProfilePageShowsWhatVisitorsSee(): void
    {
        $companyUser = $this->signIn();

        $response = $this->controller()->view($companyUser);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            'Building the backbone of tomorrow',
            (string) $response->getContent(),
        );
    }

    public function testProposingChangesStartsADraftAuthoredByTheRepresentative(): void
    {
        $companyUser = $this->signIn();
        $company = $companyUser->getCompany();
        $live = $company->getLiveRevision();

        $this->controller()->revise($companyUser);

        $draft = $company->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $draft,
        );
        self::assertSame(
            RevisionStatus::Draft,
            $draft->getStatus(),
        );
        self::assertSame(
            $companyUser,
            $draft->getAuthorCompanyUser(),
        );
        // What visitors see does not move until the committee agrees.
        self::assertSame(
            $live,
            $company->getLiveRevision(),
        );
    }

    public function testEditingIsRefusedWhileTheProfileIsWithTheCommittee(): void
    {
        $companyUser = $this->signIn();
        $session = $this->pushRequestWithSession();

        $current = $companyUser->getCompany()->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $current,
        );
        $current->setStatus(RevisionStatus::Submitted);
        $this->entityManager->flush();

        $response = $this->controller()->edit(
            new Request(),
            $companyUser,
        );

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertNotEmpty($session->getFlashBag()->peek('warning'));
    }

    /**
     * A company can only ever act on its own profile, because the company is read off the signed-in representative
     * rather than out of the URL.
     */
    public function testARepresentativeActsOnItsOwnCompanyOnly(): void
    {
        $nexunt = $this->signIn('recruitment@nexunt.example.com');
        $orbit = $this->representative('recruitment@orbit-analytics.example.com');

        $before = $orbit->getCompany()->getCurrentRevision();

        $this->controller()->revise($nexunt);

        self::assertSame(
            RevisionStatus::Draft,
            $nexunt->getCompany()->getCurrentRevision()?->getStatus(),
        );
        self::assertSame(
            $before,
            $orbit->getCompany()->getCurrentRevision(),
        );
    }

    public function testTheStatusPageShowsWhatChangedAndWhatCanBeDone(): void
    {
        $companyUser = $this->signIn();
        $this->controller()->revise($companyUser);

        $response = $this->controller()->status($companyUser);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        $content = (string) $response->getContent();
        self::assertStringContainsString(
            'What you changed',
            $content,
        );
        // A draft is the company's to submit, so the workflow offers exactly that.
        self::assertStringContainsString(
            'submit',
            $content,
        );
    }

    private function signIn(string $email = 'recruitment@nexunt.example.com'): CompanyUser
    {
        $companyUser = $this->representative($email);

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $companyUser,
            'company',
            ['ROLE_COMPANY_USER'],
        ));
        $this->pushRequestWithSession();

        return $companyUser;
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

    private function controller(): CompanyProfileController
    {
        return self::getContainer()->get(CompanyProfileController::class);
    }
}
