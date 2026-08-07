<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Application\Enums\Languages;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\VacancyLabel;
use App\Entity\Career\VacancyRevision;
use App\Form\Application\LocalisedTextType;
use App\Repository\Career\VacancyLabelRepository;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;
use function trim;

/**
 * The revisable content of a vacancy (one {@see VacancyRevision}): the localised texts, the contact details, the
 * category, the labels and the posting window. Everything here is staged with the revision and only goes live on
 * approval, so a company cannot quietly change what was agreed; {@see VacancyType} embeds this form on the vacancy's
 * working revision.
 *
 * @extends AbstractType<VacancyRevision>
 */
class VacancyRevisionType extends AbstractType
{
    public function __construct(private readonly VacancyLabelRepository $vacancyLabelRepository)
    {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $language = Languages::current();
        $localised = [
            'label' => false,
            'data_class' => CareerLocalisedText::class,
        ];

        $builder
            ->add(
                'languageDutch',
                CheckboxType::class,
                [
                    'label' => t('Dutch'),
                    'required' => false,
                    'mapped' => false,
                ],
            )
            ->add(
                'languageEnglish',
                CheckboxType::class,
                [
                    'label' => t('English'),
                    'required' => false,
                    'mapped' => false,
                ],
            )
            ->add(
                'name',
                LocalisedTextType::class,
                $localised,
            )
            ->add(
                'location',
                LocalisedTextType::class,
                $localised,
            )
            ->add(
                'website',
                LocalisedTextType::class,
                $localised,
            )
            ->add(
                'description',
                LocalisedTextType::class,
                $localised + ['multiline' => true],
            )
            ->add(
                'attachment',
                LocalisedTextType::class,
                $localised,
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label' => t('Category'),
                    'class' => VacancyCategories::class,
                ],
            )
            ->add(
                'labels',
                EntityType::class,
                [
                    'label' => t('Labels'),
                    'class' => VacancyLabel::class,
                    'choices' => $this->vacancyLabelRepository->findAll(),
                    'choice_label' => static function (VacancyLabel $label) use ($language): string {
                        return $label->getName()->getText($language) ?? '';
                    },
                    'multiple' => true,
                    'expanded' => false,
                    'autocomplete' => true,
                    'by_reference' => false,
                    'required' => false,
                ],
            )
            ->add(
                'startDate',
                DateType::class,
                [
                    'label' => t('Opens on'),
                    'help' => t('Leave empty to show the vacancy as soon as it is approved.'),
                    'widget' => 'single_text',
                    'required' => false,
                ],
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    'label' => t('Closes on'),
                    'help' => t('The last day the vacancy is shown.'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter the day the vacancy closes.')],
                ],
            )
            ->add(
                'contactName',
                TextType::class,
                [
                    'label' => t('Contact name'),
                    'required' => false,
                    'constraints' => [new Length(max: 255)],
                ],
            )
            ->add(
                'contactEmail',
                EmailType::class,
                [
                    'label' => t('Contact email address'),
                    'required' => false,
                    'constraints' => [
                        new Email(),
                        new Length(max: 255),
                    ],
                ],
            )
            ->add(
                'contactPhone',
                TextType::class,
                [
                    'label' => t('Contact phone number'),
                    'required' => false,
                    'constraints' => [new Length(max: 255)],
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->primeLanguageToggles(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => VacancyRevision::class]);
    }

    /**
     * Pre-check the language toggles based on which languages the revision already has content for. A brand-new vacancy
     * (no content in either language) defaults to English enabled so the form is immediately usable.
     */
    private function primeLanguageToggles(FormEvent $event): void
    {
        $revision = $event->getData();
        if (!$revision instanceof VacancyRevision) {
            return;
        }

        $form = $event->getForm();
        $hasDutch = $this->hasContent(
            $revision,
            true,
        );
        $hasEnglish = $this->hasContent(
            $revision,
            false,
        );

        $form->get('languageDutch')->setData($hasDutch);
        $form->get('languageEnglish')->setData($hasEnglish || !$hasDutch);
    }

    /**
     * Whether any of the revision's localised fields has non-empty content in the given language.
     */
    private function hasContent(
        VacancyRevision $revision,
        bool $dutch,
    ): bool {
        $texts = [
            $revision->getName(),
            $revision->getLocation(),
            $revision->getWebsite(),
            $revision->getDescription(),
        ];

        foreach ($texts as $text) {
            $value = $dutch
                ? $text->getValueNL()
                : $text->getValueEN();
            if (
                null !== $value
                && '' !== trim($value)
            ) {
                return true;
            }
        }

        return false;
    }
}
