<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\AdminController;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\CompanyRevisionComment;
use App\Entity\User\User;
use App\Repository\Career\CompanyRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The actions are invoked directly with the current user set on the token storage, rather than over HTTP, as the rest
 * of the controller suite does.
 */
final class AdminControllerTest extends DatabaseTestCase
{
    /**
     * A company out of contract is invisible to the public but must still be findable here, which is exactly when
     * somebody needs it. Searching and paging through the list is the component's job; see
     * {@see \App\Tests\Integration\LiveComponent\Career\Admin\CompanyOverviewTest}.
     */
    public function testTheOverviewShowsCompaniesThePublicCannotSee(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        self::assertStringContainsString(
            'Halcyon Mobility',
            $this->render(),
        );
    }

    public function testTheCompanyPageRendersItsProfilePackagesAndRepresentatives(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $response = $this->controller()->view($this->company());

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
            'Ilse Vermeer',
            $content,
        );
    }

    public function testEditingIsRefusedWhileTheProfileIsWithTheCommittee(): void
    {
        $this->authenticate();
        $session = $this->pushRequestWithSession();

        $company = $this->company();
        $current = $company->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $current,
        );
        $current->setStatus(RevisionStatus::Submitted);
        $this->entityManager->flush();

        $response = $this->controller()->edit(
            new Request(),
            $company,
            $this->user(),
        );

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertNotEmpty($session->getFlashBag()->peek('warning'));
    }

    public function testRevisingAnApprovedProfileStartsANewDraft(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $company = $this->company();
        $live = $company->getLiveRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $live,
        );

        $this->controller()->revise(
            $company,
            $this->user(),
        );

        $draft = $company->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $draft,
        );
        self::assertNotSame(
            $live,
            $draft,
        );
        self::assertSame(
            RevisionStatus::Draft,
            $draft->getStatus(),
        );
        self::assertSame(
            $live,
            $draft->getPreviousRevision(),
        );
        // The live profile is untouched until the draft is approved.
        self::assertSame(
            $live,
            $company->getLiveRevision(),
        );
    }

    public function testRevisingIsRefusedWhenADraftIsAlreadyOpen(): void
    {
        $this->authenticate();
        $session = $this->pushRequestWithSession();

        $company = $this->company();
        $this->controller()->revise(
            $company,
            $this->user(),
        );
        $draft = $company->getCurrentRevision();

        $this->controller()->revise(
            $company,
            $this->user(),
        );

        self::assertSame(
            $draft,
            $company->getCurrentRevision(),
        );
        self::assertNotEmpty($session->getFlashBag()->peek('warning'));
    }

    /**
     * The review feedback sits beside the form, so with nothing to show the form takes the full width rather than
     * leaving a gap where the panel would have been.
     */
    public function testTheEditFormOnlyMakesRoomForFeedbackWhenThereIsSome(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $company = $this->company();
        $this->controller()->revise(
            $company,
            $this->user(),
        );

        $without = $this->renderEdit($company);
        self::assertStringContainsString(
            '<div class="col-md-12">',
            $without,
        );
        self::assertStringNotContainsString(
            'Review feedback',
            $without,
        );

        $revision = $company->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $revision,
        );

        $comment = new CompanyRevisionComment();
        $comment->setRevision($revision);
        $comment->setAuthor($this->user());
        $comment->setBody('Please shorten the slogan.');
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $with = $this->renderEdit($company);
        self::assertStringContainsString(
            '<div class="col-md-9">',
            $with,
        );
        self::assertStringContainsString(
            'Please shorten the slogan.',
            $with,
        );
    }

    private function renderEdit(Company $company): string
    {
        return (string) $this->controller()->edit(
            new Request(),
            $company,
            $this->user(),
        )->getContent();
    }

    private function render(): string
    {
        return (string) $this->controller()->index()->getContent();
    }

    private function controller(): AdminController
    {
        return self::getContainer()->get(AdminController::class);
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
