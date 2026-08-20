<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\Enums\TimeOfDay;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use function Symfony\Component\Translation\t;

/**
 * One of the days a body would like its activity to fall on.
 *
 * Days rather than clock times: the calendar reserves a date, and which part of the day is wanted is a separate
 * question the board reads when two bodies want the same day. Where this date sits in the body's order of preference
 * is not asked either, it is the order the rows are in, which {@see ActivityProposalType} numbers on submit.
 *
 * @extends AbstractType<ActivityDateOption>
 */
class ActivityDateOptionType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'timeOfDay',
                EnumType::class,
                [
                    'label' => t('Part of the day'),
                    'class' => TimeOfDay::class,
                    'constraints' => [new NotNull(message: 'Say which part of the day this would take.')],
                ],
            )
            ->add(
                'beginsAt',
                DateType::class,
                [
                    'label' => t('From'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter the day this would start on.')],
                    // The entity setter is non-nullable; an empty submission would TypeError during data mapping
                    // before NotBlank ever runs, so skip the write and let NotBlank report it.
                    'setter' => static function (ActivityDateOption $option, ?DateTime $value): void {
                        if (null === $value) {
                            return;
                        }

                        $option->setBeginsAt($value);
                    },
                ],
            )
            ->add(
                'endsAt',
                DateType::class,
                [
                    'label' => t('Until'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter the day this would end on.')],
                    'setter' => static function (ActivityDateOption $option, ?DateTime $value): void {
                        if (null === $value) {
                            return;
                        }

                        $option->setEndsAt($value);
                    },
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ActivityDateOption::class]);
    }
}
