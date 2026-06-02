<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\SignupOption;
use App\Form\Application\LocalisedTextType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $builder->add(
            'value',
            LocalisedTextType::class,
            ['label' => t('Option')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SignupOption::class]);
    }
}
