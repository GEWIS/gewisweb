<?php

declare(strict_types=1);

namespace App\Form\Activity;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Tito10047\AltchaBundle\Type\AltchaType;

use function Symfony\Component\Translation\t;

/**
 * The "resend confirmation e-mail" form for an external sign-up: an e-mail address plus an Altcha captcha. The action
 * only ever re-mails an address that already has a pending sign-up and is rate-limited, but the captcha keeps it from
 * being scripted to spam that inbox. Array-backed (no `data_class`), like {@see SignupType}.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class ResendVerificationType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => t('E-mail address'),
                    'constraints' => [
                        new NotBlank(message: 'Enter an e-mail address.'),
                        new Email(message: 'Enter a valid e-mail address.'),
                        new Length(
                            max: 255,
                            maxMessage: 'The e-mail address may not be longer than {{ limit }} characters.',
                        ),
                    ],
                ],
            )
            ->add(
                'security',
                AltchaType::class,
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
