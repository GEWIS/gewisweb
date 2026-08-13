<?php

declare(strict_types=1);

namespace App\Form\Frontpage;

use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\PollRevision;
use App\Form\Application\LocalisedTextType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * Asking the association a question: the question itself and the answers it can be given. A poll is written and
 * submitted in one go, so this form is the whole of what the board is handed.
 *
 * Both languages are required. A poll is put to every member at once and there is no second one in the other
 * language, so a question written in only one leaves half the association unable to answer it. That is why there are
 * no enable-language toggles here, unlike on a body's page or a vacancy.
 *
 * @extends AbstractType<PollRevision>
 */
class PollRequestType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $required = [
            'data_class' => FrontpageLocalisedText::class,
            'value_constraints' => [
                new NotBlank(message: 'Fill this in for both languages.'),
                new Length(max: 255),
            ],
        ];

        $builder
            ->add(
                'question',
                LocalisedTextType::class,
                $required + ['label' => t('Question')],
            )
            ->add(
                'options',
                CollectionType::class,
                [
                    'label' => t('Answers'),
                    'entry_type' => PollOptionType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype_name' => '__option__',
                    'block_prefix' => 'poll_option_collection',
                    'constraints' => [
                        new Count(
                            min: 2,
                            minMessage: 'A poll needs at least two answers to choose between.',
                        ),
                    ],
                ],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PollRevision::class]);
    }
}
