<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\Enums\SignupFieldTypes;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;
use function trim;

/**
 * Create/edit form for an activity. All revisable content, including the organising organ/company and the labels,
 * lives on the embedded {@see ActivityRevisionType} bound to the activity's working revision; this root form only
 * wires that in and runs the cross-cutting scheduling/language validation that needs the whole revision at once.
 *
 * @extends AbstractType<Activity>
 */
class ActivityType extends AbstractType
{
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

        // Rule 5: at least one language must be enabled. The per-language requirements below (and on every sign-up
        // list) are all skipped for a disabled language, so with both off nothing is required and an activity with no
        // content at all would save, contradicting the form's own promise. The toggles are always submitted, so
        // this reads reliably here.
        if (
            !$dutchOn
            && !$englishOn
        ) {
            $revision->get('languageDutch')->addError(new FormError(
                $this->translator->trans(
                    'At least one language must be used.',
                    [],
                    'validators',
                ),
            ));
        }

        // Rule 6: the activity's localised texts are required for each enabled language.
        foreach (['name', 'location', 'costs', 'description'] as $field) {
            $this->requireLocalised(
                $revision->get($field),
                $dutchOn,
                $englishOn,
            );
        }

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
            $this->requireLocalised(
                $listForm->get('name'),
                $dutchOn,
                $englishOn,
            );

            foreach ($listForm->get('fields') as $fieldForm) {
                $this->requireLocalised(
                    $fieldForm->get('name'),
                    $dutchOn,
                    $englishOn,
                );

                if (SignupFieldTypes::Choice !== $fieldForm->get('type')->getData()) {
                    continue;
                }

                foreach ($fieldForm->get('options') as $optionForm) {
                    $this->requireLocalised(
                        $optionForm->get('value'),
                        $dutchOn,
                        $englishOn,
                    );
                }
            }
        }
    }

    /**
     * Require the enabled language(s) of a localised field to be filled in. A language switched off was disabled
     * client-side and not submitted, so it is never required here.
     *
     * @param FormInterface<mixed> $localised
     */
    private function requireLocalised(
        FormInterface $localised,
        bool $dutchOn,
        bool $englishOn,
    ): void {
        if (
            $dutchOn
            && '' === trim(strval($localised->get('valueNL')->getData()))
        ) {
            $localised->get('valueNL')->addError(new FormError(
                $this->translator->trans(
                    'Fill in the Dutch text.',
                    [],
                    'validators',
                ),
            ));
        }

        if (
            !$englishOn
            || '' !== trim(strval($localised->get('valueEN')->getData()))
        ) {
            return;
        }

        $localised->get('valueEN')->addError(new FormError(
            $this->translator->trans(
                'Fill in the English text.',
                [],
                'validators',
            ),
        ));
    }
}
