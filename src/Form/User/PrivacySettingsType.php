<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User\Enums\PhotoVisibility;
use App\Entity\User\UserSettings;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<UserSettings>
 */
class PrivacySettingsType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'photoTaggingOptOut',
                CheckboxType::class,
                [
                    'label' => t('Do not allow others to tag me in photos'),
                    'help' => t('Others can no longer tag you. Existing tags stay until you remove them below.'),
                    'required' => false,
                ],
            )
            ->add(
                'photoVisibility',
                EnumType::class,
                [
                    'class' => PhotoVisibility::class,
                    'label' => t('Photos I am tagged in on my photo page'),
                    'help' => t('Others only; hidden photos still show in their own album.'),
                ],
            )
            ->add(
                'hideYearOfBirth',
                CheckboxType::class,
                [
                    'label' => t('Hide my year of birth and age from other members'),
                    'help' => t('If you hide yours, you also stop seeing others\' age. The board can always see it.'),
                    'required' => false,
                ],
            )
            ->add(
                'hideBirthdayOnFrontpage',
                CheckboxType::class,
                [
                    'label' => t('Hide my birthday from the home page'),
                    'help' => t('You will no longer appear in the birthday panel on the home page.'),
                    'required' => false,
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => UserSettings::class]);
    }
}
