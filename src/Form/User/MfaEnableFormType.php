<?php

declare(strict_types=1);

namespace App\Form\User;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class MfaEnableFormType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'code',
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
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]{6}',
                    'minlength' => 6,
                    'maxlength' => 6,
                    'autofocus' => true,
                ],
            ],
        );
    }
}
