<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Form\Application\RequiresEnabledLanguagesTrait;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Create/edit form for an activity. All revisable content, including the organising organ/company and the labels,
 * lives on the embedded {@see ActivityRevisionType} bound to the activity's working revision; this root form only
 * wires that in and runs the cross-cutting scheduling/language validation that needs the whole revision at once.
 *
 * @extends AbstractType<Activity>
 */
class ActivityType extends AbstractType
{
    use RequiresEnabledLanguagesTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'currentRevision',
                ActivityRevisionType::class,
                ['label' => false],
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->validateSchedulingAndLanguages(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Activity::class]);
    }

    /**
     * Server-side business rules for the schedule, the sign-up windows and the per-language required fields. This runs
     * on the root form, the only place with access to both the revision (schedule, language toggles, localised
     * texts) and every sign-up list. Comparisons are guarded so an empty field is reported once by its NotBlank, never
     * twice here.
     */
    private function validateSchedulingAndLanguages(FormEvent $event): void
    {
        $form = $event->getForm();
        $revision = $form->get('currentRevision');

        $dutchOn = true === $revision->get('languageDutch')->getData();
        $englishOn = true === $revision->get('languageEnglish')->getData();

        $beginForm = $revision->get('beginTime');
        $endForm = $revision->get('endTime');
        $beginTime = $beginForm->getData();
        $endTime = $endForm->getData();
        $now = new DateTime();

        // Rule 1: start in the future. Skipped when the start is locked (the activity has already started).
        if (
            !$beginForm->isDisabled()
            && $beginTime instanceof DateTime
            && $beginTime <= $now
        ) {
            $beginForm->addError(new FormError(
                $this->translator->trans(
                    'The activity must start in the future.',
                    [],
                    'validators',
                ),
            ));
        }

        // Rule 2: end after start. Skipped while the start is locked.
        if (
            !$beginForm->isDisabled()
            && !$endForm->isDisabled()
            && $beginTime instanceof DateTime
            && $endTime instanceof DateTime
            && $endTime <= $beginTime
        ) {
            $endForm->addError(new FormError(
                $this->translator->trans(
                    'The end time must be after the start time.',
                    [],
                    'validators',
                ),
            ));
        }

        // Rule 2b: once the activity has started its start is locked but the end stays editable (e.g. to extend a
        // running activity). The locked start is in the past, so "after the start" no longer constrains anything;
        // require the end to be in the future instead, so it can never be moved into the past (which would make the
        // whole activity immutable).
        if (
            $beginForm->isDisabled()
            && !$endForm->isDisabled()
            && $endTime instanceof DateTime
            && $endTime <= $now
        ) {
            $endForm->addError(new FormError(
                $this->translator->trans(
                    'The end time must be in the future.',
                    [],
                    'validators',
                ),
            ));
        }

        // Rule 5: at least one language must be enabled, and rule 6: the activity's localised texts are required for
        // each enabled language.
        $this->requireAtLeastOneLanguage(
            $revision,
            $this->translator,
        );
        $this->requireEnabledLanguages(
            $revision,
            [
                'name',
                'location',
                'costs',
                'description',
            ],
            $this->translator,
        );

        foreach ($revision->get('signupLists') as $listForm) {
            $openForm = $listForm->get('openDate');
            $closeForm = $listForm->get('closeDate');
            $openDate = $openForm->getData();
            $closeDate = $closeForm->getData();

            // Rule 3a: a new sign-up list must open in the future. Skipped once the list has opened (the opening
            // date is then locked, so an already-past value is never newly rejected).
            if (
                !$openForm->isDisabled()
                && $openDate instanceof DateTime
                && $openDate <= $now
            ) {
                $openForm->addError(new FormError(
                    $this->translator->trans(
                        'The sign-up list must open in the future.',
                        [],
                        'validators',
                    ),
                ));
            }

            // Rule 3: a sign-up list must open before it closes.
            if (
                $openDate instanceof DateTime
                && $closeDate instanceof DateTime
                && $openDate >= $closeDate
            ) {
                $closeForm->addError(new FormError(
                    $this->translator->trans(
                        'The sign-up list must open before it closes.',
                        [],
                        'validators',
                    ),
                ));
            }

            // Rule 4: a sign-up list must close before the activity starts.
            if (
                $closeDate instanceof DateTime
                && $beginTime instanceof DateTime
                && $closeDate >= $beginTime
            ) {
                $closeForm->addError(new FormError(
                    $this->translator->trans(
                        'The sign-up list must close before the activity starts.',
                        [],
                        'validators',
                    ),
                ));
            }

            // Rule 6: the list name, each custom-field question and each choice option, per enabled language.
            $this->requireLocalisedText(
                $listForm->get('name'),
                $dutchOn,
                $englishOn,
                $this->translator,
            );

            foreach ($listForm->get('fields') as $fieldForm) {
                $this->requireLocalisedText(
                    $fieldForm->get('name'),
                    $dutchOn,
                    $englishOn,
                    $this->translator,
                );

                if (SignupFieldTypes::Choice !== $fieldForm->get('type')->getData()) {
                    continue;
                }

                // A choice field may preselect at most one option as its default. The editor enforces this client-side
                // (the checkboxes are mutually exclusive), so this only guards a tampered submission.
                $defaultCount = 0;
                foreach ($fieldForm->get('options') as $optionForm) {
                    $this->requireLocalisedText(
                        $optionForm->get('value'),
                        $dutchOn,
                        $englishOn,
                        $this->translator,
                    );

                    if (true !== $optionForm->get('isDefault')->getData()) {
                        continue;
                    }

                    ++$defaultCount;
                }

                if ($defaultCount <= 1) {
                    continue;
                }

                $fieldForm->addError(new FormError(
                    $this->translator->trans(
                        'Only one option can be preselected as the default.',
                        [],
                        'validators',
                    ),
                ));
            }
        }
    }
}
