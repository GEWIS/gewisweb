<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Application;

use App\Entity\Activity\Activity;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\Company;
use App\Entity\Career\Vacancy;
use App\Service\Application\RevisionDescriberRegistry;
use App\Tests\Integration\DatabaseTestCase;
use App\ViewModel\Application\Review\RevisionAudience;
use App\ViewModel\Application\Review\RevisionField;
use App\ViewModel\Application\Review\RevisionFieldKind;
use App\ViewModel\Application\Review\RevisionSection;
use Symfony\Component\Translation\TranslatableMessage;

use function array_map;

/**
 * Every revisable domain must describe itself, because the review screens no longer know how to read one. A domain
 * that has no describer throws where it used to render, so this pins that all three are registered and that each
 * answers with the sections its screens expect.
 */
final class RevisionDescriberRegistryTest extends DatabaseTestCase
{
    public function testAnActivityDescribesItself(): void
    {
        $activity = $this->entityManager->getRepository(Activity::class)->findOneBy([]);
        self::assertInstanceOf(
            Activity::class,
            $activity,
        );

        self::assertSame(
            [
                'General information',
                'Details',
            ],
            $this->headings($this->currentRevision($activity)),
        );
    }

    public function testACompanyDescribesItselfIncludingTheLogosItsOwnScreenUsedToLeaveOut(): void
    {
        $company = $this->entityManager->getRepository(Company::class)->findOneBy(['slugName' => 'nexunt']);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        $revision = $this->currentRevision($company);

        self::assertSame(
            [
                'Profile',
                'Contact details',
                'Logos',
            ],
            $this->headings($revision),
        );
        self::assertSame(
            [
                RevisionFieldKind::Image,
                RevisionFieldKind::Image,
            ],
            array_map(
                static fn (RevisionField $field): RevisionFieldKind => $field->kind,
                $this->describe(
                    $revision,
                    RevisionAudience::Everyone,
                )[2]->fields,
            ),
        );
    }

    public function testAVacancyKeepsItsOwningCompanyForTheReviewerOnly(): void
    {
        $vacancy = $this->entityManager->getRepository(Vacancy::class)->findOneBy([]);
        self::assertInstanceOf(
            Vacancy::class,
            $vacancy,
        );

        $revision = $this->currentRevision($vacancy);

        self::assertContains(
            'Company',
            $this->labels(
                $revision,
                RevisionAudience::ReviewerOnly,
            ),
        );
        self::assertNotContains(
            'Company',
            $this->labels(
                $revision,
                RevisionAudience::Everyone,
            ),
        );
        // The rest of the vacancy is the company's own, so it reads exactly what its reviewer reads.
        self::assertContains(
            'Labels',
            $this->labels(
                $revision,
                RevisionAudience::Everyone,
            ),
        );
        self::assertContains(
            'Attachment link',
            $this->labels(
                $revision,
                RevisionAudience::Everyone,
            ),
        );
    }

    private function currentRevision(Activity|Company|Vacancy $revisable): RevisionInterface
    {
        $revision = $revisable->getCurrentRevision();
        self::assertInstanceOf(
            RevisionInterface::class,
            $revision,
        );

        return $revision;
    }

    /**
     * @return list<RevisionSection>
     */
    private function describe(
        RevisionInterface $revision,
        RevisionAudience $audience = RevisionAudience::ReviewerOnly,
    ): array {
        return self::getContainer()->get(RevisionDescriberRegistry::class)->describe(
            $revision,
            $revision->getPreviousRevision(),
        )->sectionsFor($audience);
    }

    /**
     * @return list<string>
     */
    private function headings(RevisionInterface $revision): array
    {
        $headings = [];

        foreach ($this->describe($revision) as $section) {
            $heading = $section->heading;
            self::assertInstanceOf(
                TranslatableMessage::class,
                $heading,
            );
            $headings[] = $heading->getMessage();
        }

        return $headings;
    }

    /**
     * @return list<string>
     */
    private function labels(
        RevisionInterface $revision,
        RevisionAudience $audience,
    ): array {
        $labels = [];

        foreach (
            $this->describe(
                $revision,
                $audience,
            ) as $section
        ) {
            foreach ($section->fields as $field) {
                $label = $field->label;
                self::assertInstanceOf(
                    TranslatableMessage::class,
                    $label,
                );
                $labels[] = $label->getMessage();
            }
        }

        return $labels;
    }
}
