<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\CompanyPackage;
use App\Entity\Career\Enums\CompanyBannerFormats;
use App\Entity\Career\Enums\CompanyPackageTypes;
use App\Form\Application\LocalisedTextType;
use App\Form\Application\RequiresEnabledLanguagesTrait;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;
use function Symfony\Component\Translation\t;
use function trim;

/**
 * What a company bought and for how long. Every package type shares the contract number, the window and whether it is
 * published; the featured package adds the article that goes with it and the banner package the size it was sold in,
 * while the highlight package's vacancy selection is made on its own screen rather than here, since it depends on what
 * the company has running at that moment. The banner image is uploaded on its own screen for the same reason: it is
 * the one thing about the package a company changes itself.
 *
 * The type is not a field: it decides which entity is created, so the caller picks it and this form fills in the rest.
 *
 * @extends AbstractType<CompanyPackage>
 */
class CompanyPackageType extends AbstractType
{
    use RequiresEnabledLanguagesTrait;

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
                'contractNumber',
                TextType::class,
                [
                    'label' => t('Contract number'),
                    'required' => false,
                    'constraints' => [new Length(max: 255)],
                ],
            )
            ->add(
                'startingDate',
                DateType::class,
                [
                    'label' => t('Starts on'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter the day the package starts.')],
                ],
            )
            ->add(
                'expirationDate',
                DateType::class,
                [
                    'label' => t('Expires on'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter the day the package expires.')],
                ],
            )
            ->add(
                'published',
                CheckboxType::class,
                [
                    'label' => t('Published'),
                    'required' => false,
                ],
            );

        if (CompanyPackageTypes::Banner === $options['package_type']) {
            $builder->add(
                'format',
                EnumType::class,
                [
                    'label' => t('Format'),
                    'class' => CompanyBannerFormats::class,
                    'help' => t(
                        'Changing this asks the company for artwork at the new size; the banner it already has '
                        . 'stays up until it hands one over.',
                    ),
                ],
            );
        }

        if (CompanyPackageTypes::Featured === $options['package_type']) {
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
                    'article',
                    LocalisedTextType::class,
                    [
                        'label' => false,
                        'data_class' => CareerLocalisedText::class,
                        'multiline' => true,
                    ],
                );

            $builder->addEventListener(
                FormEvents::POST_SET_DATA,
                $this->primeLanguageToggles(...),
            );
        }

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->validateWindow(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->validateArticle(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompanyPackage::class,
            'package_type' => CompanyPackageTypes::Job,
        ]);
        $resolver->setAllowedTypes(
            'package_type',
            CompanyPackageTypes::class,
        );
    }

    /**
     * Pre-check the language toggles based on which languages the article already has, so opening an existing featured
     * package shows the languages it is actually written in. A new one defaults to English.
     */
    private function primeLanguageToggles(FormEvent $event): void
    {
        $package = $event->getData();
        if (!$package instanceof CompanyFeaturedPackage) {
            return;
        }

        $article = $package->getArticle();
        $hasDutch = '' !== trim(strval($article->getValueNL()));
        $hasEnglish = '' !== trim(strval($article->getValueEN()));

        $form = $event->getForm();
        $form->get('languageDutch')->setData($hasDutch);
        $form->get('languageEnglish')->setData($hasEnglish || !$hasDutch);
    }

    private function validateArticle(FormEvent $event): void
    {
        $form = $event->getForm();
        if (!$form->has('article')) {
            return;
        }

        $this->requireAtLeastOneLanguage(
            $form,
            $this->translator,
        );
        $this->requireEnabledLanguages(
            $form,
            ['article'],
            $this->translator,
        );
    }

    private function validateWindow(FormEvent $event): void
    {
        $form = $event->getForm();
        $start = $form->get('startingDate')->getData();
        $expires = $form->get('expirationDate')->getData();

        if (
            !$start instanceof DateTime
            || !$expires instanceof DateTime
            || $expires > $start
        ) {
            return;
        }

        $form->get('expirationDate')->addError(new FormError(
            $this->translator->trans(
                'A package cannot expire before it starts.',
                [],
                'validators',
            ),
        ));
    }
}
