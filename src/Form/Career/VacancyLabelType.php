<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Application\LocalisedText;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\VacancyLabel;
use App\Form\Application\LocalisedTextType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\Translation\t;
use function trim;

/**
 * A label a vacancy can be tagged with, in both languages plus a short form for the badge. Labels are shared reference
 * data rather than revisable content, so this form writes straight through.
 *
 * A label is one or two words carried by every vacancy tagged with it, so there is no enabling a language here the way
 * there is on the revisable content: both translations are required.
 *
 * @extends AbstractType<VacancyLabel>
 */
class VacancyLabelType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'name',
                LocalisedTextType::class,
                [
                    'label' => t('Name'),
                    'data_class' => CareerLocalisedText::class,
                ],
            )
            ->add(
                'abbreviation',
                LocalisedTextType::class,
                [
                    'label' => t('Abbreviation'),
                    'data_class' => CareerLocalisedText::class,
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->requireBothLanguages(...),
        );
    }

    public function requireBothLanguages(FormEvent $event): void
    {
        $label = $event->getData();
        if (!$label instanceof VacancyLabel) {
            return;
        }

        $form = $event->getForm();
        $this->requireBoth(
            $form->get('name'),
            $label->getName(),
            $this->translator->trans('Enter the name in both languages.'),
        );
        $this->requireBoth(
            $form->get('abbreviation'),
            $label->getAbbreviation(),
            $this->translator->trans('Enter the abbreviation in both languages.'),
        );
    }

    /**
     * @param FormInterface<array<string, mixed>> $field
     */
    private function requireBoth(
        FormInterface $field,
        LocalisedText $text,
        string $message,
    ): void {
        foreach (
            [
                'valueNL' => $text->getValueNL(),
                'valueEN' => $text->getValueEN(),
            ] as $child => $value
        ) {
            if ('' !== trim($value ?? '')) {
                continue;
            }

            $field->get($child)->addError(new FormError($message));
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => VacancyLabel::class]);
    }
}
