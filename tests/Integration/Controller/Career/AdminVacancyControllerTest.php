<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Career;

use App\Controller\Career\AdminVacancyController;
use App\Controller\Career\AdminVacancyLabelController;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyLabel;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class AdminVacancyControllerTest extends DatabaseTestCase
{
    /**
     * Filtering and paging through the list is the component's job; see
     * {@see \App\Tests\Integration\LiveComponent\Career\Admin\VacancyOverviewTest}.
     */
    public function testTheOverviewShowsEveryCompanysVacancies(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $content = $this->overview();

        self::assertStringContainsString(
            'Backend Engineer',
            $content,
        );
        self::assertStringContainsString(
            'Master Thesis',
            $content,
        );
    }

    public function testAnApprovedVacancyIsRevisedRatherThanEdited(): void
    {
        $this->authenticate();
        $session = $this->pushRequestWithSession();

        $vacancy = $this->vacancy('backend-engineer');
        $live = $vacancy->getLiveRevision();

        $response = $this->controller()->edit(
            new Request(),
            $vacancy,
            $this->user(),
        );
        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertNotEmpty($session->getFlashBag()->get('warning'));

        $this->controller()->revise(
            $vacancy,
            $this->user(),
        );

        $draft = $vacancy->getCurrentRevision();
        self::assertInstanceOf(
            VacancyRevision::class,
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
        // The window is part of the content, so the draft starts from what was agreed.
        self::assertEquals(
            $live?->getEndDate(),
            $draft->getEndDate(),
        );
    }

    public function testALabelStillInUseCannotBeRemoved(): void
    {
        $this->authenticate();
        $session = $this->pushRequestWithSession();

        $label = $this->labelInUse();
        $labelId = (int) $label->getId();

        $this->labelController()->delete($label);

        self::assertNotNull($this->entityManager->getRepository(VacancyLabel::class)->find($labelId));
        self::assertNotEmpty($session->getFlashBag()->peek('warning'));
    }

    public function testALabelNobodyUsesCanBeRemoved(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $label = new VacancyLabel();
        $label->setName(new CareerLocalisedText(
            'Temporary',
            'Tijdelijk',
        ));
        $label->setAbbreviation(new CareerLocalisedText(
            'TMP',
            'TMP',
        ));
        $this->entityManager->persist($label);
        $this->entityManager->flush();
        $labelId = (int) $label->getId();

        $this->labelController()->delete($label);

        self::assertNull($this->entityManager->getRepository(VacancyLabel::class)->find($labelId));
    }

    private function overview(): string
    {
        $response = $this->controller()->index();
        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        return (string) $response->getContent();
    }

    private function labelInUse(): VacancyLabel
    {
        foreach ($this->entityManager->getRepository(VacancyLabel::class)->findAll() as $label) {
            if ($label->getRevisions()->isEmpty()) {
                continue;
            }

            return $label;
        }

        self::fail('The seed is expected to contain a label that is in use.');
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

    private function controller(): AdminVacancyController
    {
        return self::getContainer()->get(AdminVacancyController::class);
    }

    private function labelController(): AdminVacancyLabelController
    {
        return self::getContainer()->get(AdminVacancyLabelController::class);
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
