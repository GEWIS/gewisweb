<?php

declare(strict_types=1);

namespace App\Form\Photo;

use App\Entity\Photo\Album;
use App\Repository\Photo\AlbumRepository;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * Create/edit form for a photo {@see Album}. The album name is single-language (no localised text), and the cover is
 * generated from the album's photos, so it is not a form field. The `album` option is the album being edited (null when
 * creating); it is excluded — together with its descendants — from the parent choices so an album cannot become its own
 * ancestor.
 *
 * @extends AbstractType<Album>
 */
final class AlbumType extends AbstractType
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $current = $options['album'];
        assert(null === $current || $current instanceof Album);

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
                'parent',
                EntityType::class,
                [
                    'label' => t('Parent album'),
                    'class' => Album::class,
                    'choice_label' => 'name',
                    'required' => false,
                    'placeholder' => t('No parent album (a root album)'),
                    'choices' => $this->albumRepository->findAssignableParents($current),
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
        $resolver->setDefaults([
            'data_class' => Album::class,
            'album' => null,
        ]);
        $resolver->setAllowedTypes(
            'album',
            [
                'null',
                Album::class,
            ],
        );
    }
}
