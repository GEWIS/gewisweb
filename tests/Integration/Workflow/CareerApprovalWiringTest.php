<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workflow;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\User;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\CompanyRevisionRepository;
use App\Repository\Career\VacancyRevisionRepository;
use App\Service\Application\RevisionDiscarder;
use App\Tests\Integration\DatabaseTestCase;
use App\Workflow\RevisionClonerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * The generic revision workflow covers the career chains by instanceof, so nothing about it was written for them. These
 * pin that it genuinely does: a company profile and a vacancy go all the way round, promoting the live version at the
 * end, and turn up in the review queues while they are with the committee.
 */
final class CareerApprovalWiringTest extends DatabaseTestCase
{
    public function testACompanyProfileGoesFromDraftToLive(): void
    {
        $this->authenticateCompanyAdmin();
        $company = $this->company();
        $live = $company->getLiveRevision();
        $draft = $this->cloner()->cloneAsDraft($this->companyHead());
        self::assertInstanceOf(
            CompanyRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        foreach (
            [
                'submit',
                'start_review',
                'approve',
            ] as $transition
        ) {
            self::assertTrue(
                $this->workflow($draft)->can(
                    $draft,
                    $transition,
                ),
                $transition,
            );
            $this->workflow($draft)->apply(
                $draft,
                $transition,
            );
        }

        $this->entityManager->flush();

        self::assertSame(
            RevisionStatus::Approved,
            $draft->getStatus(),
        );
        // Approving is what makes it public: the company now points at the new revision instead of the old one.
        self::assertSame(
            $draft,
            $company->getLiveRevision(),
        );
        self::assertNotSame(
            $live,
            $company->getLiveRevision(),
        );
    }

    public function testAProfileWaitingForTheCommitteeTurnsUpInTheQueue(): void
    {
        $this->authenticateCompanyAdmin();
        $draft = $this->cloner()->cloneAsDraft($this->companyHead());
        self::assertInstanceOf(
            CompanyRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->entityManager->flush();

        self::assertContains(
            $draft,
            self::getContainer()->get(CompanyRevisionRepository::class)->findForReview(),
        );
    }

    public function testAVacancyWaitingForTheCommitteeTurnsUpInTheQueue(): void
    {
        $this->authenticateCompanyAdmin();
        $draft = $this->cloner()->cloneAsDraft($this->vacancyHead());
        self::assertInstanceOf(
            VacancyRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->entityManager->flush();

        self::assertContains(
            $draft,
            self::getContainer()->get(VacancyRevisionRepository::class)->findForReview(),
        );
    }

    /**
     * Asking for changes spawns the next draft on its own, which is what the company then works on.
     */
    public function testAskingForChangesLeavesTheCompanyANewDraft(): void
    {
        $this->authenticateCompanyAdmin();
        $company = $this->company();
        $draft = $this->cloner()->cloneAsDraft($this->companyHead());
        self::assertInstanceOf(
            CompanyRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->workflow($draft)->apply(
            $draft,
            'start_review',
        );
        $this->workflow($draft)->apply(
            $draft,
            'request_changes',
        );
        $this->entityManager->flush();

        $next = $company->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $next,
        );
        self::assertNotSame(
            $draft,
            $next,
        );
        self::assertSame(
            RevisionStatus::Draft,
            $next->getStatus(),
        );
        self::assertSame(
            $draft,
            $next->getPreviousRevision(),
        );
    }

    public function testDiscardingADraftPutsTheLiveProfileBack(): void
    {
        $this->authenticateCompanyAdmin();
        $company = $this->company();
        $live = $company->getLiveRevision();
        $draft = $this->cloner()->cloneAsDraft($this->companyHead());
        self::assertInstanceOf(
            CompanyRevision::class,
            $draft,
        );
        // Something that would be lost with the draft, so the discard has to take it too.
        $draft->setSlogan(new CareerLocalisedText(
            'Changed',
            'Gewijzigd',
        ));
        $this->entityManager->persist($draft);
        $this->entityManager->flush();
        $draftId = (int) $draft->getId();

        self::getContainer()->get(RevisionDiscarder::class)->discardToLive($draft);
        $this->entityManager->flush();

        self::assertSame(
            $live,
            $company->getCurrentRevision(),
        );
        self::assertNull($this->entityManager->getRepository(CompanyRevision::class)->find($draftId));
    }

    /**
     * The company-admin role is what the approve guard checks; the account must be a real seeded one, since the voter
     * and the review-stamp listener read its member.
     */
    private function authenticateCompanyAdmin(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_COMPANY_ADMIN'],
        ));
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

    private function companyHead(string $slug = 'nexunt'): CompanyRevision
    {
        $current = $this->company($slug)->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $current,
        );

        return $current;
    }

    private function vacancyHead(string $slug = 'backend-engineer'): VacancyRevision
    {
        $current = $this->vacancy($slug)->getCurrentRevision();
        self::assertInstanceOf(
            VacancyRevision::class,
            $current,
        );

        return $current;
    }

    private function vacancy(string $slug = 'backend-engineer'): Vacancy
    {
        $vacancy = $this->entityManager->getRepository(Vacancy::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Vacancy::class,
            $vacancy,
        );

        return $vacancy;
    }

    private function cloner(): RevisionClonerRegistry
    {
        return self::getContainer()->get(RevisionClonerRegistry::class);
    }

    private function workflow(CompanyRevision|VacancyRevision $revision): WorkflowInterface
    {
        return self::getContainer()->get(Registry::class)->get(
            $revision,
            'revision',
        );
    }
}
