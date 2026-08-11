<?php

declare(strict_types=1);

namespace App\Form\Application;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;
use function floatval;
use function is_array;
use function strval;

/**
 * The rectangle a crop picker chose, as fractions of the image it was shown. Fractions rather than pixels, so the
 * rectangle means the same thing whichever rendition of the original the picker happened to display.
 *
 * The four boxes are hidden: they are written by the `image-crop` Stimulus controller, never typed into. An untouched
 * field comes back as null, which is how "leave the crop alone" is said.
 *
 * @extends AbstractType<array<string, float>|null>
 */
class CropRectangleType extends AbstractType
{
    private const array PARTS = [
        'x',
        'y',
        'width',
        'height',
    ];

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        foreach (self::PARTS as $part) {
            $builder->add(
                $part,
                HiddenType::class,
                ['required' => false],
            );
        }

        $builder->addModelTransformer(new CallbackTransformer(
            static function (?array $rectangle): array {
                $values = [];

                foreach (self::PARTS as $part) {
                    $values[$part] = null === $rectangle
                        ? ''
                        : strval($rectangle[$part] ?? '');
                }

                return $values;
            },
            static function (mixed $submitted): ?array {
                if (!is_array($submitted)) {
                    return null;
                }

                $rectangle = [];

                foreach (self::PARTS as $part) {
                    if (
                        !array_key_exists(
                            $part,
                            $submitted,
                        )
                        || '' === $submitted[$part]
                        || null === $submitted[$part]
                    ) {
                        return null;
                    }

                    $rectangle[$part] = floatval($submitted[$part]);
                }

                return $rectangle;
            },
        ));
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'data_class' => null,
            'empty_data' => null,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'crop_rectangle';
    }
}
