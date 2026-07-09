<?php

declare(strict_types=1);

namespace App\Form\Application;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use function array_intersect;
use function in_array;
use function Symfony\Component\Translation\t;

/**
 * The review decision for a revision: one submit button per enabled workflow transition (passed as the
 * `enabled_transitions` option), plus a contextual message field. The message is the reviewer's feedback when a
 * decision is available (mandatory to reject or request changes). When `resubmission` is set and `submit` is
 * available, the message is instead the organiser's mandatory response explaining how the requested changes were
 * addressed. Actions that need no message (a fresh submit, start review, close) get no field at all, so the screen
 * never shows a stray, mislabelled box. Mandatory-ness is enforced through the buttons' validation groups, so no
 * controller-side checking is needed. Generic across every revisable domain (activities, companies, vacancies).
 *
 * @extends AbstractType<array<string, mixed>>
 */
class ReviewDecisionType extends AbstractType
{
    /**
     * Transitions whose decision requires the reviewer to leave feedback first.
     */
    private const array FEEDBACK_TRANSITIONS = [
        'reject',
        'request_changes',
    ];

    /**
     * Transitions that constitute a reviewer's verdict; their presence is what shows the feedback field.
     */
    private const array DECISION_TRANSITIONS = [
        'approve',
        'reject',
        'request_changes',
    ];

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        /** @var string[] $enabled */
        $enabled = $options['enabled_transitions'];
        $isReviewerDecision = [] !== array_intersect(
            self::DECISION_TRANSITIONS,
            $enabled,
        );
        $isResubmission = true === $options['resubmission']
            && in_array(
                'submit',
                $enabled,
                true,
            );

        // The message field is contextual. When a reviewer can decide it is their feedback (mandatory to reject or
        // request changes). When an organiser resubmits a draft that addressed a "changes requested" review it is
        // their mandatory response. For actions that need no message (a fresh submit, start review, close) the field
        // is absent entirely, so the screen never shows a stray, mislabelled box next to "Add a comment".
        if ($isReviewerDecision) {
            $builder->add(
                'message',
                TextareaType::class,
                [
                    'label' => t('Feedback'),
                    'help' => t('Required to request changes or reject.'),
                    'required' => false,
                    'attr' => ['rows' => 4],
                    'constraints' => [
                        new NotBlank(
                            message: 'Feedback is required when requesting changes or rejecting.',
                            groups: ['feedback_required'],
                        ),
                    ],
                ],
            );
        } elseif ($isResubmission) {
            $builder->add(
                'message',
                TextareaType::class,
                [
                    'label' => t('Respond to the requested changes'),
                    'help' => t('Explain how you addressed the feedback before resubmitting.'),
                    'required' => true,
                    'attr' => ['rows' => 4],
                    'constraints' => [
                        new NotBlank(
                            message: 'Describe how you addressed the requested changes before resubmitting.',
                            groups: ['response_required'],
                        ),
                    ],
                ],
            );
        }

        // Explicit `t()` calls keep every label extractable; only the enabled ones are actually added as buttons.
        // `submit` is the author-side transition; the rest are the board's.
        $labels = [
            'submit' => t('Submit for review'),
            'start_review' => t('Start review'),
            'approve' => t('Approve'),
            'request_changes' => t('Request changes'),
            'reject' => t('Reject'),
            'close' => t('Close'),
        ];

        foreach ($enabled as $transition) {
            if (!isset($labels[$transition])) {
                continue;
            }

            $builder->add(
                $transition,
                SubmitType::class,
                [
                    'label' => $labels[$transition],
                    'validation_groups' => $this->validationGroups(
                        $transition,
                        $isResubmission,
                    ),
                ],
            );
        }
    }

    /**
     * The validation group a clicked transition button activates: resubmitting requires the organiser's response,
     * rejecting / requesting changes require the reviewer's feedback, everything else validates nothing.
     *
     * @return string[]|false
     */
    private function validationGroups(
        string $transition,
        bool $isResubmission,
    ): array|false {
        if ('submit' === $transition) {
            return $isResubmission
                ? ['response_required']
                : false;
        }

        return in_array(
            $transition,
            self::FEEDBACK_TRANSITIONS,
            true,
        )
            ? ['feedback_required']
            : false;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'enabled_transitions',
            [],
        );
        $resolver->setAllowedTypes(
            'enabled_transitions',
            'string[]',
        );
        $resolver->setDefault(
            'resubmission',
            false,
        );
        $resolver->setAllowedTypes(
            'resubmission',
            'bool',
        );
    }
}
