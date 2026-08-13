<?php

declare(strict_types=1);

namespace App\Form\Frontpage;

use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\PollOption;
use App\Form\Application\LocalisedTextType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * One of the answers a poll can be given, in both languages.
 *
 * Rendered through the `poll_option` theme block, which lays the two languages out in the columns the panel around it
 * already heads with a flag, so an answer does not repeat them.
 *
 * @extends AbstractType<PollOption>
 */
class PollOptionType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'text',
            LocalisedTextType::class,
            [
                'label' => t('Answer'),
                'data_class' => FrontpageLocalisedText::class,
                'value_constraints' => [
                    new NotBlank(message: 'Fill this in for both languages.'),
                    new Length(max: 255),
                ],
            ],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PollOption::class,
            'block_prefix' => 'poll_option',
        ]);
    }
}
