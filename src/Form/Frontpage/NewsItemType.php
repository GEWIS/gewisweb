<?php

declare(strict_types=1);

namespace App\Form\Frontpage;

use App\Entity\Frontpage\Enums\NewsCategory;
use App\Entity\Frontpage\NewsItem;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * A piece of news, written in both languages. The bodies are markdown, edited with the same editor as an activity's
 * description.
 *
 * @extends AbstractType<NewsItem>
 */
class NewsItemType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'date',
                DateType::class,
                [
                    'label' => t('Date'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter the date this news item carries.')],
                ],
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label' => t('Category'),
                    'class' => NewsCategory::class,
                ],
            )
            ->add(
                'pinned',
                CheckboxType::class,
                [
                    'label' => t('Pin to the top of the news'),
                    'required' => false,
                ],
            )
            ->add(
                'dutchTitle',
                TextType::class,
                [
                    'label' => t('Title'),
                    'constraints' => [
                        new NotBlank(message: 'Enter the Dutch title.'),
                        new Length(max: 255),
                    ],
                ],
            )
            ->add(
                'englishTitle',
                TextType::class,
                [
                    'label' => t('Title'),
                    'constraints' => [
                        new NotBlank(message: 'Enter the English title.'),
                        new Length(max: 255),
                    ],
                ],
            )
            ->add(
                'dutchContent',
                TextareaType::class,
                [
                    'label' => t('Content'),
                    'constraints' => [new NotBlank(message: 'Write the Dutch text.')],
                ],
            )
            ->add(
                'englishContent',
                TextareaType::class,
                [
                    'label' => t('Content'),
                    'constraints' => [new NotBlank(message: 'Write the English text.')],
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => NewsItem::class]);
    }
}
