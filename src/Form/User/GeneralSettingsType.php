<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User\UserSettings;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

/**
 * @extends AbstractType<UserSettings>
 */
class GeneralSettingsType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'disableCosmetics',
            CheckboxType::class,
            [
                'label' => t('Disable festive effects'),
                'help' => t('Turn off the balloons, snow, and fireworks across the website.'),
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
