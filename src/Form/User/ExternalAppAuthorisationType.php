<?php

declare(strict_types=1);

namespace App\Form\User;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Confirmation form for letting an external application authenticate as the member. The reminder variant is shown when
 * the member already authorised the application before, but more than 90 days ago.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class ExternalAppAuthorisationType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        if (true === $options['reminder']) {
            $builder->add(
                'continue',
                SubmitType::class,
                [
                    'label' => 'Continue',
                ],
            );

            return;
        }

        $builder->add(
            'cancel',
            SubmitType::class,
            [
                'label' => 'Cancel',
            ],
        );
        $builder->add(
            'authorise',
            SubmitType::class,
            [
                'label' => 'Authorise',
            ],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'reminder' => false,
        ]);
        $resolver->setAllowedTypes(
            'reminder',
            'bool',
        );
    }
}
