<?php

declare(strict_types=1);

namespace App\Form\User;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class SudoConfirmFormType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'password',
            PasswordType::class,
            [
                'mapped' => false,
                'label' => t('Password'),
                'attr' => [
                    'autocomplete' => 'current-password',
                    'autofocus' => true,
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your password.'),
                ],
            ],
        );

        if (true !== $options['mfa_required']) {
            return;
        }

        $builder->add(
            'mfaCode',
            TextType::class,
            [
                'mapped' => false,
                'label' => t(
                    'auth_code',
                    [],
                    'SchebTwoFactorBundle',
                ),
                'attr' => [
                    'autocomplete' => 'one-time-code',
                    'autocapitalize' => 'none',
                    'spellcheck' => 'false',
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your verification code.'),
                    new Regex(
                        pattern: '/^(\d{6}|[0-9a-f]{16})$/',
                        message: 'Enter a 6-digit authenticator code or a 16-character lowercase-hex backup code.',
                    ),
                ],
            ],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault(
                'mfa_required',
                false,
            )
            ->setAllowedTypes(
                'mfa_required',
                'bool',
            );
    }
}
