<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\CompanyRevision;
use App\Form\Application\LocalisedTextType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

use function Symfony\Component\Translation\t;
use function trim;

/**
 * The revisable content of a company (one {@see CompanyRevision}): the localised texts, the logo and the contact
 * details. Everything here is staged with the revision and only goes live on approval; {@see CompanyType} embeds this
 * form on the company's working revision and owns the fields that survive across revisions.
 *
 * The `languageDutch` / `languageEnglish` checkboxes are unmapped: they only drive the `localised-fields` Stimulus
 * controller, which enables the Dutch respectively English variant of every localised field. A disabled variant is not
 * submitted, so an unchecked language keeps whatever it already had.
 *
 * @extends AbstractType<CompanyRevision>
 */
class CompanyRevisionType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
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
                'slogan',
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
            )
            ->add(
                'contactAddress',
                TextType::class,
                [
                    'label' => t('Address'),
                    'required' => false,
                    'constraints' => [new Length(max: 255)],
                ],
            )
            // Unmapped: the upload is stored by the controller, which puts the resulting path on the revision. Leaving
            // it empty keeps whatever logo the revision already carries.
            ->add(
                'logoFile',
                FileType::class,
                [
                    'label' => t('Logo'),
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [
                        new File(
                            maxSize: '8M',
                            mimeTypes: [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ],
                            mimeTypesMessage: 'Upload a JPEG, PNG or WebP image.',
                        ),
                    ],
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
        $resolver->setDefaults(['data_class' => CompanyRevision::class]);
    }

    /**
     * Pre-check the language toggles based on which languages the revision already has content for. A brand-new company
     * (no content in either language) defaults to English enabled so the form is immediately usable.
     */
    private function primeLanguageToggles(FormEvent $event): void
    {
        $revision = $event->getData();
        if (!$revision instanceof CompanyRevision) {
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
        CompanyRevision $revision,
        bool $dutch,
    ): bool {
        $texts = [
            $revision->getSlogan(),
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
