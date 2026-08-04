<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\CompanyVacancyController;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\CompanyUser;
use App\Repository\User\CompanyUserRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class CompanyVacancyControllerTest extends DatabaseTestCase
{
    public function testTheListShowsWhatIsLiveAndWhatIsNot(): void
    {
        $companyUser = $this->signIn();

        $response = $this->controller()->index(
            new Request(),
            $companyUser,
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        $content = (string) $response->getContent();
        self::assertStringContainsString(
            'Backend Engineer',
            $content,
        );
        // The one whose window closed is still the company's, so it is listed but not shown as live.
        self::assertStringContainsString(
            'Platform Engineering Internship',
            $content,
        );
    }

    /**
     * The tabs narrow the list to one state at a time, and a vacancy sits in exactly one of them.
     */
    public function testATabOnlyShowsTheVacanciesInThatState(): void
    {
        $companyUser = $this->signIn();

        $response = $this->controller()->index(
            new Request(['filter' => 'closed']),
            $companyUser,
        );

        $content = (string) $response->getContent();
        self::assertStringContainsString(
            'Platform Engineering Internship',
            $content,
        );
        self::assertStringNotContainsString(
            'Backend Engineer',
            $content,
        );
    }

    public function testAnotherCompanysVacancyIsNotReachable(): void
    {
        $companyUser = $this->signIn();

        $this->expectException(NotFoundHttpException::class);
        $this->controller()->status(
            $this->vacancy('data-science-internship'),
            $companyUser,
        );
    }

    public function testProposingChangesStartsADraftAuthoredByTheRepresentative(): void
    {
        $companyUser = $this->signIn();
        $vacancy = $this->vacancy('backend-engineer');
        $live = $vacancy->getLiveRevision();

        $this->controller()->revise(
            $vacancy,
            $companyUser,
        );

        $draft = $vacancy->getCurrentRevision();
        self::assertInstanceOf(
            VacancyRevision::class,
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
        self::assertSame(
            $live,
            $vacancy->getLiveRevision(),
        );
    }

    public function testEditingIsRefusedWhileTheVacancyIsWithTheCommittee(): void
    {
        $companyUser = $this->signIn();
        $session = $this->pushRequestWithSession();

        $vacancy = $this->vacancy('backend-engineer');
        $current = $vacancy->getCurrentRevision();
        self::assertInstanceOf(
            VacancyRevision::class,
            $current,
        );
        $current->setStatus(RevisionStatus::Submitted);
        $this->entityManager->flush();

        $response = $this->controller()->edit(
            new Request(),
            $vacancy,
            $companyUser,
        );

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertNotEmpty($session->getFlashBag()->peek('warning'));
    }

    public function testDiscardingADraftRestoresTheLiveVacancy(): void
    {
        $companyUser = $this->signIn();
        $this->pushRequestWithSession();

        $vacancy = $this->vacancy('backend-engineer');
        $live = $vacancy->getLiveRevision();
        $this->controller()->revise(
            $vacancy,
            $companyUser,
        );
        $draft = $vacancy->getCurrentRevision();
        self::assertNotSame(
            $live,
            $draft,
        );
        $draftId = (int) $draft?->getId();

        $this->controller()->discard(
            $vacancy,
            $companyUser,
        );

        self::assertSame(
            $live,
            $vacancy->getCurrentRevision(),
        );
        self::assertNull($this->entityManager->getRepository(VacancyRevision::class)->find($draftId));
    }

    /**
     * A company may only post under a contract of its own that is still running.
     */
    public function testTheCreateFormOnlyOffersItsOwnRunningPackages(): void
    {
        $companyUser = $this->signIn();

        $response = $this->controller()->create(
            new Request(),
            $companyUser,
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        $content = (string) $response->getContent();
        self::assertStringContainsString(
            'Nexunt Systems',
            $content,
        );
        self::assertStringNotContainsString(
            'Orbit Analytics',
            $content,
        );
    }

    private function vacancy(string $slug): Vacancy
    {
        $vacancy = $this->entityManager->getRepository(Vacancy::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Vacancy::class,
            $vacancy,
        );

        return $vacancy;
    }

    private function signIn(string $email = 'recruitment@nexunt.example.com'): CompanyUser
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

    private function controller(): CompanyVacancyController
    {
        return self::getContainer()->get(CompanyVacancyController::class);
    }
}
