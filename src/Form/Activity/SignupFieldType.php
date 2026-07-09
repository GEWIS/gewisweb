<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\SignupField;
use App\Form\Application\LocalisedTextType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

/**
 * A custom question on a sign-up list ({@see SignupField}). The "number" type uses the min/max bounds; the "choice"
 * type uses the nested options collection.
 *
 * @extends AbstractType<SignupField>
 */
class SignupFieldType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'name',
                LocalisedTextType::class,
                ['label' => t('Question')],
            )
            ->add(
                'type',
                EnumType::class,
                [
                    'label' => t('Type'),
                    'class' => SignupFieldTypes::class,
                ],
            )
            ->add(
                'isSensitive',
                CheckboxType::class,
                [
                    'label' => t('Sensitive (only visible to the board and organiser)'),
                    'required' => false,
                ],
            )
            ->add(
                'minimumValue',
                IntegerType::class,
                [
                    'label' => t('Minimum value (number type)'),
                    'required' => false,
                ],
            )
            ->add(
                'maximumValue',
                IntegerType::class,
                [
                    'label' => t('Maximum value (number type)'),
                    'required' => false,
                ],
            )
            ->add(
                'options',
                CollectionType::class,
                [
                    'label' => false,
                    'entry_type' => SignupOptionType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    'prototype_name' => '__option__',
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SignupField::class]);
    }
}
