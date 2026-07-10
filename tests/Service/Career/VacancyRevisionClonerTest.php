<?php

declare(strict_types=1);

namespace App\Tests\Service\Career;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyLabel;
use App\Entity\Career\VacancyRevision;
use App\Entity\Decision\Member;
use App\Service\Career\VacancyRevisionCloner;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * Spawning the next vacancy draft must produce an independent copy: the localised texts (name, location, website,
 * description, attachment) become fresh rows (the OneToOne relations are orphan-removing, so a shared row would be
 * deleted with the source), while the category and labels are shared reference entities carried over by reference and
 * the contact details are scalars copied by value. These tests pin that contract so a regression cannot blank an
 * editor's draft, drop its labels, or delete live content.
 */
final class VacancyRevisionClonerTest extends TestCase
{
    private VacancyRevisionCloner $cloner;

    #[Override]
    protected function setUp(): void
    {
        $this->cloner = new VacancyRevisionCloner();
    }

    public function testStartsADraftLinkedIntoTheChainCarryingTheAuthor(): void
    {
        $author = self::createStub(Member::class);
        $source = $this->approvedSource(author: $author);
        $source->setRevisionNumber(5);
        $vacancy = $source->getVacancy();

        $draft = $this->cloner->cloneAsDraft($source);
        self::assertInstanceOf(
            VacancyRevision::class,
            $draft,
        );

        self::assertSame(
            $source,
            $draft->getPreviousRevision(),
        );
        self::assertSame(
            $draft,
            $vacancy->getCurrentRevision(),
        );
        self::assertTrue($vacancy->getRevisions()->contains($draft));
        self::assertSame(
            RevisionStatus::Draft,
            $draft->getStatus(),
        );
        self::assertSame(
            6,
            $draft->getRevisionNumber(),
        );
        self::assertSame(
            $author,
            $draft->getAuthor(),
        );
        self::assertNull($draft->getAuthorCompanyUser());
    }

    public function testDeepCopiesTextsButCarriesCategoryAndLabelsByReference(): void
    {
        $category = VacancyCategories::Internships;
        $label = self::createStub(VacancyLabel::class);
        $source = $this->approvedSource(
            category: $category,
            label: $label,
        );

        $draft = $this->cloner->cloneAsDraft($source);
        self::assertInstanceOf(
            VacancyRevision::class,
            $draft,
        );

        $this->assertCopiedNotShared(
            $source->getName(),
            $draft->getName(),
        );
        $this->assertCopiedNotShared(
            $source->getLocation(),
            $draft->getLocation(),
        );
        $this->assertCopiedNotShared(
            $source->getWebsite(),
            $draft->getWebsite(),
        );
        $this->assertCopiedNotShared(
            $source->getDescription(),
            $draft->getDescription(),
        );
        $this->assertCopiedNotShared(
            $source->getAttachment(),
            $draft->getAttachment(),
        );

        // The category (an enum case) and labels are carried over, so the draft points at the very same instances.
        self::assertSame(
            $category,
            $draft->getCategory(),
        );
        self::assertTrue($draft->getLabels()->contains($label));

        // Contact details are scalars copied by value.
        self::assertSame(
            'Jane Doe',
            $draft->getContactName(),
        );
        self::assertSame(
            '+31 600000000',
            $draft->getContactPhone(),
        );
        self::assertSame(
            'jane@example.com',
            $draft->getContactEmail(),
        );
    }

    private function assertCopiedNotShared(
        CareerLocalisedText $source,
        CareerLocalisedText $draft,
    ): void {
        self::assertNotSame(
            $source,
            $draft,
            'localised text must be a fresh row, not the shared (orphan-removing) source row',
        );
        self::assertSame(
            $source->getValueNL(),
            $draft->getValueNL(),
        );
        self::assertSame(
            $source->getValueEN(),
            $draft->getValueEN(),
        );
    }

    private function approvedSource(
        ?Member $author = null,
        ?VacancyCategories $category = null,
        ?VacancyLabel $label = null,
    ): VacancyRevision {
        $vacancy = new Vacancy();

        $source = new VacancyRevision();
        $vacancy->addRevision($source);
        $vacancy->setCurrentRevision($source);

        $source->setStatus(RevisionStatus::Approved);
        $source->setRevisionNumber(1);
        $source->setAuthor($author ?? self::createStub(Member::class));
        $source->setName($this->text(
            'Engineer',
            'Ingenieur',
        ));
        $source->setLocation($this->text(
            'Eindhoven',
            'Eindhoven',
        ));
        $source->setWebsite($this->text(
            'https://example.com/en',
            'https://example.com/nl',
        ));
        $source->setDescription($this->text(
            'A role.',
            'Een functie.',
        ));
        $source->setAttachment($this->text(
            'brochure-en.pdf',
            'brochure-nl.pdf',
        ));
        $source->setContactName('Jane Doe');
        $source->setContactPhone('+31 600000000');
        $source->setContactEmail('jane@example.com');
        $source->setCategory($category ?? VacancyCategories::Jobs);
        $source->addLabel($label ?? self::createStub(VacancyLabel::class));

        return $source;
    }

    private function text(
        string $en,
        string $nl,
    ): CareerLocalisedText {
        return new CareerLocalisedText(
            $en,
            $nl,
        );
    }
}
