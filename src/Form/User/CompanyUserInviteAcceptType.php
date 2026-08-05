<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Validator\User\PasswordPolicy;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

use function Symfony\Component\Translation\t;

/**
 * The password an invited representative chooses. Unlike the other password forms this one has no account to hash into
 * yet, so the plain password is read off the form and the account is built around it.
 *
 * @extends AbstractType<null>
 */
class CompanyUserInviteAcceptType extends AbstractType
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
                    'constraints' => PasswordPolicy::constraints(),
                    'label' => t('Password'),
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
