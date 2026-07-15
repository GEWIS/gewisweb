<?php

declare(strict_types=1);

namespace App\Form\Photo;

use App\Entity\Photo\Album;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * Create/edit form for a photo {@see Album}. The album name is single-language (no localised text), and the cover is
 * generated from the album's photos, so neither is a form field. A sub-album is created from its parent's manage view,
 * so the parent is not chosen here either.
 *
 * @extends AbstractType<Album>
 */
final class AlbumType extends AbstractType
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
                'name',
                TextType::class,
                [
                    'label' => t('Name'),
                    'constraints' => [new NotBlank(message: 'Enter an album name.')],
                ],
            )
            ->add(
                'startDateTime',
                DateTimeType::class,
                [
                    'label' => t('Start date and time'),
                    'widget' => 'single_text',
                    'required' => false,
                ],
            )
            ->add(
                'endDateTime',
                DateTimeType::class,
                [
                    'label' => t('End date and time'),
                    'widget' => 'single_text',
                    'required' => false,
                ],
            )
            ->add(
                'published',
                CheckboxType::class,
                [
                    'label' => t('Published'),
                    'help' => t('Unpublished albums are only visible to the board.'),
                    'required' => false,
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'data_class',
            Album::class,
        );
    }
}
