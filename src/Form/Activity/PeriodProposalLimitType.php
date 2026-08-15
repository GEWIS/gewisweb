<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\PeriodProposalLimit;
use App\Entity\Decision\Organ;
use App\Repository\Decision\OrganRepository;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotNull;

use function Symfony\Component\Translation\t;

/**
 * An exception for one body in one round, which beats that body's standing exception and the round's own number.
 *
 * The round itself is not a field: this form is only ever reached from the round it belongs to.
 *
 * @extends AbstractType<PeriodProposalLimit>
 */
class PeriodProposalLimitType extends AbstractType
{
    public function __construct(private readonly OrganRepository $organRepository)
    {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'organ',
                EntityType::class,
                [
                    'label' => t('Body'),
                    'class' => Organ::class,
                    'choices' => $this->organRepository->findActive(),
                    'choice_label' => 'abbr',
                    'autocomplete' => true,
                    'placeholder' => t('Select a body'),
                    'constraints' => [new NotNull(message: 'Pick the body this applies to.')],
                    'disabled' => null !== $options['data']?->getId(),
                ],
            )
            ->add(
                'maxProposals',
                IntegerType::class,
                [
                    'label' => t('Activities it may propose in this round'),
                    'help' => t('Zero stops this body from proposing anything in this round.'),
                    'constraints' => [
                        new GreaterThanOrEqual(
                            value: 0,
                            message: 'A body cannot be allowed a negative number of activities.',
                        ),
                    ],
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PeriodProposalLimit::class]);
    }
}
