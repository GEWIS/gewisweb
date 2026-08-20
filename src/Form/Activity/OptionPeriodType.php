<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\OptionPeriod;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\Translation\t;

/**
 * A round of the option calendar. Two windows that are easy to confuse, so the labels spell out which is which: when
 * bodies may hand proposals in, and which days the activities they propose have to fall on.
 *
 * There is no list of bodies here on purpose. The board sets exceptions on the limits screen, one row per exception,
 * and everybody else is answered by the default. The calendar this replaces asked for a number per body every time a
 * round was opened, pre-filled every one of them with zero, and shut out any body founded afterwards.
 *
 * @extends AbstractType<OptionPeriod>
 */
class OptionPeriodType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Name'),
                    'help' => t('What the board calls this round, for example "Q1 2026-2027".'),
                    'constraints' => [
                        new NotBlank(message: 'Give this round a name.'),
                        new Length(
                            min: 2,
                            max: 128,
                            minMessage: 'The name should be at least {{ limit }} characters.',
                            maxMessage: 'The name should be at most {{ limit }} characters.',
                        ),
                    ],
                ],
            )
            ->add(
                'submissionOpensAt',
                DateTimeType::class,
                [
                    'label' => t('Bodies may propose from'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter when bodies may start proposing.')],
                    // The entity setter is non-nullable; an empty submission would TypeError during data mapping
                    // before NotBlank ever runs, so skip the write and let NotBlank report it.
                    'setter' => static function (OptionPeriod $period, ?DateTime $value): void {
                        if (null === $value) {
                            return;
                        }

                        $period->setSubmissionOpensAt($value);
                    },
                ],
            )
            ->add(
                'submissionClosesAt',
                DateTimeType::class,
                [
                    'label' => t('Bodies may propose until'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter when proposing closes.')],
                    'setter' => static function (OptionPeriod $period, ?DateTime $value): void {
                        if (null === $value) {
                            return;
                        }

                        $period->setSubmissionClosesAt($value);
                    },
                ],
            )
            ->add(
                'startsAt',
                DateType::class,
                [
                    'label' => t('First day activities may fall on'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter the first day of this round.')],
                    'setter' => static function (OptionPeriod $period, ?DateTime $value): void {
                        if (null === $value) {
                            return;
                        }

                        $period->setStartsAt($value);
                    },
                ],
            )
            ->add(
                'endsAt',
                DateType::class,
                [
                    'label' => t('Last day activities may fall on'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter the last day of this round.')],
                    'setter' => static function (OptionPeriod $period, ?DateTime $value): void {
                        if (null === $value) {
                            return;
                        }

                        $period->setEndsAt($value);
                    },
                ],
            )
            ->add(
                'defaultMaxProposals',
                IntegerType::class,
                [
                    'label' => t('Activities each body may propose'),
                    'help' => t('Leave empty to use the usual number. A body with an exception is not affected.'),
                    'required' => false,
                    'constraints' => [
                        new GreaterThanOrEqual(
                            value: 0,
                            message: 'A body cannot be allowed a negative number of activities.',
                        ),
                    ],
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->validateWindows(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OptionPeriod::class]);
    }

    /**
     * Both windows have to run forwards. Nothing stops them overlapping each other or another round: bodies routinely
     * propose for a quartile while the previous one is running, and a proposal names the round it is for.
     */
    private function validateWindows(FormEvent $event): void
    {
        $period = $event->getData();
        $form = $event->getForm();

        if (!$period instanceof OptionPeriod) {
            return;
        }

        if (
            $form->get('submissionOpensAt')->getData() instanceof DateTime
            && $form->get('submissionClosesAt')->getData() instanceof DateTime
            && $period->getSubmissionClosesAt() <= $period->getSubmissionOpensAt()
        ) {
            $form->get('submissionClosesAt')->addError(new FormError(
                $this->translator->trans(
                    'Proposing must close after it opens.',
                    [],
                    'validators',
                ),
            ));
        }

        if (
            !($form->get('startsAt')->getData() instanceof DateTime)
            || !($form->get('endsAt')->getData() instanceof DateTime)
            || $period->getEndsAt() >= $period->getStartsAt()
        ) {
            return;
        }

        $form->get('endsAt')->addError(new FormError(
            $this->translator->trans(
                'The last day of the round must not be before the first.',
                [],
                'validators',
            ),
        ));
    }
}
