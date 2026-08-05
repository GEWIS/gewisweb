<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener\Career;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\User;
use App\Message\Application\PublishDomainNotificationMessage;
use App\Message\Career\CareerReviewDecisionEmail;
use App\Repository\Career\CompanyRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Workflow\RevisionClonerRegistry;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

use function array_map;

/**
 * Both listeners hang off the shared revision workflow, so they see every domain's transitions and have to ignore the
 * ones that are not theirs. These pin that they do, and that a career transition reaches the right side: the committee
 * gets a notification when something is submitted, the company gets an email when it is decided.
 */
final class CareerNotificationWiringTest extends DatabaseTestCase
{
    public function testSubmittingAProfileTellsTheCommittee(): void
    {
        $draft = $this->companyDraft();
        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->entityManager->flush();

        self::assertContains(
            NotificationType::CompanyRevisionAwaitingReview,
            $this->publishedKinds(),
        );
        self::assertContains(
            $draft->getId(),
            array_map(
                static fn (PublishDomainNotificationMessage $message): int => $message->getSubjectId(),
                $this->sent(PublishDomainNotificationMessage::class),
            ),
        );
    }

    public function testSubmittingAVacancyTellsTheCommittee(): void
    {
        $draft = $this->vacancyDraft();
        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->entityManager->flush();

        self::assertContains(
            NotificationType::VacancyRevisionAwaitingReview,
            $this->publishedKinds(),
        );
    }

    /**
     * The activity chain runs through the same workflow, so the career listeners have to let it pass.
     */
    public function testAnActivitySubmissionIsNotTakenForACareerOne(): void
    {
        $draft = $this->companyDraft();
        $this->workflow($draft)->apply(
            $draft,
            'submit',
        );
        $this->entityManager->flush();

        foreach ($this->sent(PublishDomainNotificationMessage::class) as $message) {
            self::assertNotSame(
                NotificationType::ActivityAwaitingReview,
                $message->getType(),
            );
        }
    }

    public function testApprovingWritesToTheCompany(): void
    {
        $draft = $this->companyDraft();
        $workflow = $this->workflow($draft);
        $workflow->apply(
            $draft,
            'submit',
        );
        $workflow->apply(
            $draft,
            'start_review',
        );
        $workflow->apply(
            $draft,
            'approve',
        );
        $this->entityManager->flush();

        $emails = $this->sent(CareerReviewDecisionEmail::class);
        self::assertNotEmpty($emails);

        self::assertContains(
            RevisionStatus::Approved,
            array_map(
                static fn (CareerReviewDecisionEmail $email): RevisionStatus => $email->getOutcome(),
                $emails,
            ),
        );
        self::assertContains(
            'Nexunt Systems',
            array_map(
                static fn (CareerReviewDecisionEmail $email): string => $email->getSubjectName(),
                $emails,
            ),
        );
    }

    public function testAskingForChangesWritesToTheCompanyToo(): void
    {
        $draft = $this->companyDraft();
        $workflow = $this->workflow($draft);
        $workflow->apply(
            $draft,
            'submit',
        );
        $workflow->apply(
            $draft,
            'start_review',
        );
        $workflow->apply(
            $draft,
            'request_changes',
        );
        $this->entityManager->flush();

        $emails = $this->sent(CareerReviewDecisionEmail::class);
        self::assertNotEmpty($emails);
        self::assertContains(
            RevisionStatus::ChangesRequested,
            array_map(
                static fn (CareerReviewDecisionEmail $email): RevisionStatus => $email->getOutcome(),
                $emails,
            ),
        );
    }

    private function companyDraft(): CompanyRevision
    {
        $this->authenticate();

        $company = $this->company();
        $current = $company->getCurrentRevision();
        self::assertInstanceOf(
            CompanyRevision::class,
            $current,
        );

        $draft = self::getContainer()->get(RevisionClonerRegistry::class)->cloneAsDraft($current);
        self::assertInstanceOf(
            CompanyRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $draft;
    }

    private function vacancyDraft(): VacancyRevision
    {
        $this->authenticate();

        $vacancy = $this->entityManager->getRepository(Vacancy::class)
            ->findOneBy(['slugName' => 'backend-engineer']);
        self::assertNotNull($vacancy);

        $current = $vacancy->getCurrentRevision();
        self::assertInstanceOf(
            VacancyRevision::class,
            $current,
        );

        $draft = self::getContainer()->get(RevisionClonerRegistry::class)->cloneAsDraft($current);
        self::assertInstanceOf(
            VacancyRevision::class,
            $draft,
        );
        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $draft;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    private function sent(string $class): array
    {
        $messages = [];
        foreach (
            [
                'messenger.transport.normal_priority',
                'messenger.transport.high_priority',
            ] as $name
        ) {
            $transport = self::getContainer()->get($name);
            self::assertInstanceOf(
                InMemoryTransport::class,
                $transport,
            );

            foreach ($transport->getSent() as $envelope) {
                $message = $envelope->getMessage();
                if (!$message instanceof $class) {
                    continue;
                }

                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * @return list<NotificationType>
     */
    private function publishedKinds(): array
    {
        return array_map(
            static fn (PublishDomainNotificationMessage $message): NotificationType => $message->getType(),
            $this->sent(PublishDomainNotificationMessage::class),
        );
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

    private function workflow(CompanyRevision|VacancyRevision $revision): WorkflowInterface
    {
        return self::getContainer()->get(Registry::class)->get(
            $revision,
            'revision',
        );
    }

    private function authenticate(): void
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
}
