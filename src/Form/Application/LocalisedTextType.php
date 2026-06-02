<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Application\LocalisedText as LocalisedTextModel;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

/**
 * Reusable sub-form for an {@see LocalisedTextModel}: a Dutch and an English value. The owning module passes its own
 * `data_class` (e.g. {@see ActivityLocalisedText} or a Career localised text). Values are read/written through the
 * entity's accessors via the field `getter`/`setter`, so the shared base needs no form-only setters.
 *
 * @extends AbstractType<LocalisedTextModel>
 */
class LocalisedTextType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $widget = true === $options['multiline']
            ? TextareaType::class
            : TextType::class;

        $builder
            ->add(
                'valueNL',
                $widget,
                [
                    'label' => t('Dutch'),
                    'required' => false,
                    'getter' => static fn (LocalisedTextModel $text): ?string => $text->getValueNL(),
                    'setter' => static function (
                        LocalisedTextModel $text,
                        ?string $value,
                    ): void {
                        $text->updateValueNL($value);
                    },
                ],
            )
            ->add(
                'valueEN',
                $widget,
                [
                    'label' => t('English'),
                    'required' => false,
                    'getter' => static fn (LocalisedTextModel $text): ?string => $text->getValueEN(),
                    'setter' => static function (
                        LocalisedTextModel $text,
                        ?string $value,
                    ): void {
                        $text->updateValueEN($value);
                    },
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActivityLocalisedText::class,
            'multiline' => false,
        ]);
        $resolver->setAllowedTypes(
            'multiline',
            'bool',
        );
    }
}
