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

use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<PasswordAuthenticatedUserInterface>
 */
class SetPasswordFormType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
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
                    'hash_property_path' => 'password',
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
