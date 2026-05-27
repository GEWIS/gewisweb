<?php

declare(strict_types=1);

namespace App\Form\User;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class PasswordResetRequestFormType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'email',
            EmailType::class,
            [
                'label' => t('Email address'),
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ],
        );

        // Add membership number only when required.
        if (true !== $options['require_membership']) {
            return;
        }

        $builder->add(
            'membershipNumber',
            IntegerType::class,
            [
                'label' => t('Membership number'),
                'constraints' => [
                    new NotBlank(),
                ],
            ],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'require_membership' => true,
        ]);
    }
}
