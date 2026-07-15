<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\SignupOption;
use App\Form\Application\LocalisedTextType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function strval;
use function Symfony\Component\Translation\t;

/**
 * One selectable choice of a {@see SignupOption} (for a "choice" sign-up field).
 *
 * @extends AbstractType<SignupOption>
 */
class SignupOptionType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'value',
                LocalisedTextType::class,
                ['label' => t('Option')],
            )
            ->add(
                'isDefault',
                CheckboxType::class,
                [
                    'label' => t('Default'),
                    'required' => false,
                    // Rendered as a checkbox but made mutually exclusive per field by the signup-field controller
                    // (checking one clears the others), so at most one option is the default.
                    'attr' => [
                        'data-signup-field-target' => 'defaultOption',
                        'data-action' => 'change->signup-field#defaultChanged',
                    ],
                ],
            );

        // Options are reorderable too (see the sortable Stimulus controller); carry the dragged order in a hidden
        // input, transformed to/from the entity's int position like the field's own position.
        $builder->add(
            'position',
            HiddenType::class,
            [
                'attr' => ['data-sortable-target' => 'position'],
            ],
        );
        $builder->get('position')->addModelTransformer(new CallbackTransformer(
            static fn (?int $value): string => strval($value ?? 0),
            static fn (?string $value): int => (int) $value,
        ));
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SignupOption::class]);
    }
}
