<?php

declare(strict_types=1);

namespace App\Tests\Form\Application;

use App\Form\Application\ReviewDecisionType;
use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

/**
 * The review form must offer exactly one button per enabled workflow transition and a message field whose presence and
 * mandatory-ness depend on context: it is the reviewer's feedback (mandatory to reject/request changes) when a decision
 * is on offer, the organiser's mandatory response when resubmitting, and absent for actions that need no message (a
 * fresh submit, start review, close). Mandatory-ness rides on each button's validation group, so these tests pin which
 * group every button activates; the form's entire correctness contract depends on that wiring.
 */
// TypeTestCase creates an unconfigured EventDispatcher mock internally; opt out of the no-expectations notice.
#[AllowMockObjectsWithoutExpectations]
final class ReviewDecisionTypeTest extends TypeTestCase
{
    /**
     * @return list<FormExtensionInterface>
     */
    #[Override]
    protected function getExtensions(): array
    {
        // The `constraints` / `validation_groups` options are contributed by the validator form extension.
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testAReviewerDecisionShowsAFeedbackFieldGatedByButton(): void
    {
        $form = $this->factory->create(
            ReviewDecisionType::class,
            null,
            [
                'enabled_transitions' => [
                    'start_review',
                    'approve',
                    'request_changes',
                    'reject',
                ],
            ],
        );

        self::assertTrue($form->has('message'));

        // Rejecting and requesting changes demand feedback; approving and starting a review do not.
        self::assertSame(
            ['feedback_required'],
            $form->get('reject')->getConfig()->getOption('validation_groups'),
        );
        self::assertSame(
            ['feedback_required'],
            $form->get('request_changes')->getConfig()->getOption('validation_groups'),
        );
        // The validator extension normalises a `false` validation group to an empty list ("validate nothing").
        self::assertSame(
            [],
            $form->get('approve')->getConfig()->getOption('validation_groups'),
        );
        self::assertSame(
            [],
            $form->get('start_review')->getConfig()->getOption('validation_groups'),
        );
    }

    public function testAFreshSubmitOffersNoMessageField(): void
    {
        $form = $this->factory->create(
            ReviewDecisionType::class,
            null,
            ['enabled_transitions' => ['submit']],
        );

        self::assertFalse($form->has('message'));
        self::assertTrue($form->has('submit'));
        self::assertSame(
            [],
            $form->get('submit')->getConfig()->getOption('validation_groups'),
        );
    }

    public function testResubmittingAddsAMandatoryResponseField(): void
    {
        $form = $this->factory->create(
            ReviewDecisionType::class,
            null,
            [
                'enabled_transitions' => ['submit'],
                'resubmission' => true,
            ],
        );

        self::assertTrue($form->has('message'));
        self::assertSame(
            ['response_required'],
            $form->get('submit')->getConfig()->getOption('validation_groups'),
        );
    }

    public function testActionsThatNeedNoMessageGetNoField(): void
    {
        $form = $this->factory->create(
            ReviewDecisionType::class,
            null,
            ['enabled_transitions' => ['close']],
        );

        self::assertFalse($form->has('message'));
        self::assertTrue($form->has('close'));
    }

    public function testOnlyEnabledTransitionsBecomeButtons(): void
    {
        $form = $this->factory->create(
            ReviewDecisionType::class,
            null,
            ['enabled_transitions' => ['approve']],
        );

        self::assertTrue($form->has('approve'));
        self::assertFalse($form->has('reject'));
        self::assertFalse($form->has('submit'));
    }
}
