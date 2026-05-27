<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Validator\User\PasswordPolicy;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<PasswordAuthenticatedUserInterface>
 */
class ChangePasswordFormType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'currentPassword',
                PasswordType::class,
                [
                    'label' => t('Current password'),
                    'mapped' => false,
                    'attr' => [
                        'autocomplete' => 'current-password',
                    ],
                    'constraints' => [
                        new NotBlank(message: 'Please enter your current password.'),
                    ],
                ],
            )
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'options' => [
                        'attr' => [
                            'autocomplete' => 'new-password',
                        ],
                    ],
                    'first_options' => [
                        'constraints' => PasswordPolicy::constraints(),
                        'label' => t('New password'),
                    ],
                    'second_options' => [
                        'label' => t('Repeat password'),
                    ],
                    'invalid_message' => 'The password fields must match.',
                    'mapped' => false,
                ],
            );
    }
}
