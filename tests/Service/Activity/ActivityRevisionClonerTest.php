<?php

declare(strict_types=1);

namespace App\Tests\Service\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityLabel;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\SignupOption;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Service\Activity\ActivityRevisionCloner;
use DateTime;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * Spawning draft N+1 must produce an *independent* copy of the source revision's content: the localised texts have to
 * become fresh rows (the relations are orphan-removing, so a shared row would be deleted with the source), the
 * schedule must be cloned by value, the reference entities (organ, company, labels) must be carried over by reference,
 * and the sign-up lists must be deep-cloned -- keeping their lineage id so approval can migrate the live sign-ups onto
 * them, but never carrying the sign-ups themselves (those stay on the live revision until approval). These tests pin
 * that contract; a regression here silently blanks an editor's draft or, worse, lets a draft delete live content.
 */
final class ActivityRevisionClonerTest extends TestCase
{
    private ActivityRevisionCloner $cloner;

    #[Override]
    protected function setUp(): void
    {
        $this->cloner = new ActivityRevisionCloner();
    }

    public function testLinksTheDraftIntoTheChainAsTheNewWorkingHead(): void
    {
        $source = $this->approvedSource();
        $activity = $source->getActivity();

        $draft = $this->cloner->cloneAsDraft($source);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );

        self::assertSame(
            $source,
            $draft->getPreviousRevision(),
        );
        self::assertSame(
            $activity,
            $draft->getActivity(),
        );
        self::assertSame(
            $draft,
            $activity->getCurrentRevision(),
        );
        self::assertTrue($activity->getRevisions()->contains($draft));
    }

    public function testStartsAsDraftNumberedAfterTheSourceCarryingItsAuthor(): void
    {
        $author = self::createStub(Member::class);
        $source = $this->approvedSource($author);
        $source->setRevisionNumber(3);

        $draft = $this->cloner->cloneAsDraft($source);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );

        // A spawned draft always reopens as an editable Draft, one past the source, with the source's authorship
        // carried forward (a controller may reassign it afterwards).
        self::assertSame(
            RevisionStatus::Draft,
            $draft->getStatus(),
        );
        self::assertSame(
            4,
            $draft->getRevisionNumber(),
        );
        self::assertSame(
            $author,
            $draft->getAuthor(),
        );
        self::assertNull($draft->getAuthorCompanyUser());
    }

    public function testDeepCopiesTheLocalisedTextsIntoFreshRows(): void
    {
        $source = $this->approvedSource();

        $draft = $this->cloner->cloneAsDraft($source);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );

        // The texts must be distinct instances with equal values: the OneToOne relations are orphan-removing, so a
        // shared row would be deleted out from under the source revision when the draft is later discarded.
        $this->assertCopiedNotShared(
            $source->getName(),
            $draft->getName(),
        );
        $this->assertCopiedNotShared(
            $source->getLocation(),
            $draft->getLocation(),
        );
        $this->assertCopiedNotShared(
            $source->getCosts(),
            $draft->getCosts(),
        );
        $this->assertCopiedNotShared(
            $source->getDescription(),
            $draft->getDescription(),
        );
    }

    public function testClonesTheScheduleByValueAndCopiesTheFlagsAndCategory(): void
    {
        $source = $this->approvedSource();

        $draft = $this->cloner->cloneAsDraft($source);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );

        // The schedule is mutable state, so it is cloned (equal value, distinct instance) rather than shared.
        self::assertEquals(
            $source->getBeginTime(),
            $draft->getBeginTime(),
        );
        self::assertNotSame(
            $source->getBeginTime(),
            $draft->getBeginTime(),
        );
        self::assertEquals(
            $source->getEndTime(),
            $draft->getEndTime(),
        );
        self::assertNotSame(
            $source->getEndTime(),
            $draft->getEndTime(),
        );

        self::assertSame(
            ActivityCategories::Workshop,
            $draft->getCategory(),
        );
        self::assertTrue($draft->getRequireGEFLITST());
        self::assertTrue($draft->getRequireZettle());
    }

    public function testCarriesTheReferenceEntitiesOverByReference(): void
    {
        $organ = self::createStub(Organ::class);
        $company = self::createStub(Company::class);
        $label = self::createStub(ActivityLabel::class);
        $source = $this->approvedSource(
            organ: $organ,
            company: $company,
            label: $label,
        );

        $draft = $this->cloner->cloneAsDraft($source);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );

        // Organ, company and labels are shared reference entities (not owned content), so the draft points at the very
        // same instances rather than copies.
        self::assertSame(
            $organ,
            $draft->getOrgan(),
        );
        self::assertSame(
            $company,
            $draft->getCompany(),
        );
        self::assertTrue($draft->getLabels()->contains($label));
    }

    public function testDeepClonesSignupListsKeepingLineageButDroppingSignups(): void
    {
        $source = $this->approvedSource();
        $sourceList = $source->getSignupLists()->getValues()[0];

        $draft = $this->cloner->cloneAsDraft($source);
        self::assertInstanceOf(
            ActivityRevision::class,
            $draft,
        );

        $draftLists = $draft->getSignupLists()->getValues();
        self::assertCount(
            1,
            $draftLists,
        );
        $draftList = $draftLists[0];

        // A fresh list owned by the draft, but on the same lineage so approval can migrate the live sign-ups onto it.
        self::assertNotSame(
            $sourceList,
            $draftList,
        );
        self::assertSame(
            $draft,
            $draftList->getRevision(),
        );
        self::assertTrue($draftList->getLineageId()->equals($sourceList->getLineageId()));
        // The sign-ups are deliberately NOT carried: they stay on the live revision until the approval migration.
        self::assertTrue($draftList->getSignUps()->isEmpty());
        self::assertFalse($sourceList->getSignUps()->isEmpty());

        // Fields and options are deep-cloned (distinct instances, equal layout) so editing the draft cannot mutate the
        // live list's structure.
        $sourceField = $sourceList->getFields()->getValues()[0];
        $draftField = $draftList->getFields()->getValues()[0];
        self::assertNotSame(
            $sourceField,
            $draftField,
        );
        self::assertSame(
            $sourceField->getType(),
            $draftField->getType(),
        );
        $this->assertCopiedNotShared(
            $sourceField->getName(),
            $draftField->getName(),
        );

        $sourceOption = $sourceField->getOptions()->getValues()[0];
        $draftOption = $draftField->getOptions()->getValues()[0];
        self::assertNotSame(
            $sourceOption,
            $draftOption,
        );
        $this->assertCopiedNotShared(
            $sourceOption->getValue(),
            $draftOption->getValue(),
        );
    }

    private function assertCopiedNotShared(
        ActivityLocalisedText $source,
        ActivityLocalisedText $draft,
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

    /**
     * An approved source revision attached to an activity, populated across every kind of field the cloner handles:
     * owned localised texts, a mutable schedule, value flags/category, reference entities, and a sign-up list that
     * already has a sign-up (which must NOT be carried over).
     */
    private function approvedSource(
        ?Member $author = null,
        ?Organ $organ = null,
        ?Company $company = null,
        ?ActivityLabel $label = null,
    ): ActivityRevision {
        $activity = new Activity();

        $source = new ActivityRevision();
        $activity->addRevision($source);
        $activity->setCurrentRevision($source);

        $source->setStatus(RevisionStatus::Approved);
        $source->setRevisionNumber(1);
        $source->setAuthor($author ?? self::createStub(Member::class));
        $source->setName($this->text(
            'Lecture',
            'College',
        ));
        $source->setLocation($this->text(
            'Aula',
            'Aula',
        ));
        $source->setCosts($this->text(
            'Free',
            'Gratis',
        ));
        $source->setDescription($this->text(
            'A talk.',
            'Een praatje.',
        ));
        $source->setBeginTime(new DateTime('2026-07-01 18:00'));
        $source->setEndTime(new DateTime('2026-07-01 22:00'));
        $source->setCategory(ActivityCategories::Workshop);
        $source->setRequireGEFLITST(true);
        $source->setRequireZettle(true);
        $source->setOrgan($organ ?? self::createStub(Organ::class));
        $source->setCompany($company ?? self::createStub(Company::class));
        $source->addLabel($label ?? self::createStub(ActivityLabel::class));
        $source->addSignupList($this->signupListWithSignup());

        return $source;
    }

    private function signupListWithSignup(): SignupList
    {
        $list = new SignupList();
        $list->setName($this->text(
            'Attendees',
            'Aanwezigen',
        ));

        $field = new SignupField();
        $field->setName($this->text(
            'Colour',
            'Kleur',
        ));
        $field->setType(SignupFieldTypes::Choice);

        $option = new SignupOption();
        $option->setValue($this->text(
            'Red',
            'Rood',
        ));
        $field->addOption($option);
        $list->addField($field);

        $signup = new ExternalSignup();
        $signup->setSignupList($list);
        $list->getSignUps()->add($signup);

        return $list;
    }

    private function text(
        string $en,
        string $nl,
    ): ActivityLocalisedText {
        return new ActivityLocalisedText(
            $en,
            $nl,
        );
    }
}
