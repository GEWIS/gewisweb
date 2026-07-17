<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User\Enums\JWTClaims;
use App\Entity\User\ExternalApp;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * Admin create/edit form for a registered {@see ExternalApp}.
 *
 * @extends AbstractType<ExternalApp>
 */
final class ExternalAppType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'appId',
                TextType::class,
                [
                    'label' => t('Application identifier'),
                    'help' => t('Used in the authentication URL (/user/token/{identifier}).'),
                    'constraints' => [new NotBlank(message: 'Enter an application identifier.')],
                ],
            )
            ->add(
                'secret',
                TextType::class,
                [
                    'label' => t('Secret'),
                    'help' => t('Signs the token. Share it only with the application, and rotate it yearly.'),
                    'constraints' => [new NotBlank(message: 'Enter a secret.')],
                ],
            )
            ->add(
                'callback',
                UrlType::class,
                [
                    'label' => t('Callback URL'),
                    'help' => t('Where the member is sent with the token after authenticating.'),
                    'constraints' => [new NotBlank(message: 'Enter a callback URL.')],
                ],
            )
            ->add(
                'url',
                UrlType::class,
                [
                    'label' => t('Application URL'),
                    'help' => t('Where the member is sent when they decline.'),
                    'constraints' => [new NotBlank(message: 'Enter an application URL.')],
                ],
            )
            ->add(
                'claims',
                EnumType::class,
                [
                    'class' => JWTClaims::class,
                    'label' => t('Claims'),
                    'help' => t('The information the token carries about the member.'),
                    'choice_label' => static fn (JWTClaims $claim) => $claim->label(),
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                ],
            )
            ->add(
                'enabled',
                CheckboxType::class,
                [
                    'label' => t('Enabled'),
                    'help' => t('Disabled applications can no longer authenticate.'),
                    'required' => false,
                ],
            )
            ->add(
                'expiresAt',
                DateTimeType::class,
                [
                    'label' => t('Expires at'),
                    'help' => t('After this the application can no longer authenticate. Leave empty for no expiry.'),
                    'widget' => 'single_text',
                    'required' => false,
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'data_class',
            ExternalApp::class,
        );
    }
}
