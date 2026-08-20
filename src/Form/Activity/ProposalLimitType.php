<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\ProposalLimit;
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
 * A standing exception for one body, which holds until the board changes it.
 *
 * Only the exceptions are ever written down. There is no row, and no field on this form, for a body on the ordinary
 * number, so opening a round costs the board nothing and a body founded halfway through one still gets an allowance.
 *
 * @extends AbstractType<ProposalLimit>
 */
class ProposalLimitType extends AbstractType
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
                    // The body an exception is about is what makes it unique, so it is settled when the row is created.
                    'disabled' => null !== $options['data']?->getId(),
                ],
            )
            ->add(
                'maxProposals',
                IntegerType::class,
                [
                    'label' => t('Activities it may propose per round'),
                    'help' => t('Zero stops this body from proposing anything at all.'),
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
        $resolver->setDefaults(['data_class' => ProposalLimit::class]);
    }
}
