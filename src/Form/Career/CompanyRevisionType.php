<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\CompanyRevision;
use App\Form\Application\LocalisedTextType;
use App\Form\Application\SocialLinksType;
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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;

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
    private const string MAXIMUM_SIZE = '8M';

    private const array MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const int SQUARE_MINIMUM = 320;

    private const int BANNER_MINIMUM_WIDTH = 640;

    private const float SQUARE_MINIMUM_RATIO = 0.9;

    private const float SQUARE_MAXIMUM_RATIO = 1.1;

    private const float BANNER_MINIMUM_RATIO = 1.5;

    private const float BANNER_MAXIMUM_RATIO = 6.0;

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
            ->add(
                'socialLinks',
                SocialLinksType::class,
                [
                    // The form works in handles; turning those into rows is the revision's business, because that is
                    // the side the foreign key lives on.
                    'getter' => static fn (CompanyRevision $revision): array => $revision->getSocialHandles(),
                    'setter' => static function (
                        CompanyRevision $revision,
                        array $handles,
                    ): void {
                        $revision->updateSocialLinks($handles);
                    },
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->primeLanguageToggles(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->addLogoFields(...),
        );
    }

    /**
     * Unmapped: the controller stores the upload and puts the path on the revision. Each field is required until the
     * revision carries that logo, which is why they can only be built once the revision is known.
     */
    private function addLogoFields(FormEvent $event): void
    {
        $revision = $event->getData();
        $form = $event->getForm();

        $form->add(
            'squareLogoFile',
            FileType::class,
            [
                'label' => t('Square logo'),
                'help' => t(
                    'Your mark on its own, square, at least %size% by %size% pixels.',
                    ['%size%' => self::SQUARE_MINIMUM],
                ),
                'required' => null === $revision?->getSquareLogo(),
                'mapped' => false,
                'constraints' => $this->logoConstraints(
                    $revision?->getSquareLogo(),
                    new Image(
                        maxSize: self::MAXIMUM_SIZE,
                        mimeTypes: self::MIME_TYPES,
                        mimeTypesMessage: 'Upload a JPEG, PNG or WebP image.',
                        minWidth: self::SQUARE_MINIMUM,
                        minHeight: self::SQUARE_MINIMUM,
                        maxRatio: self::SQUARE_MAXIMUM_RATIO,
                        minRatio: self::SQUARE_MINIMUM_RATIO,
                    ),
                ),
            ],
        );

        $form->add(
            'bannerLogoFile',
            FileType::class,
            [
                'label' => t('Banner logo'),
                'help' => t(
                    'Your mark and name side by side, at least %width% pixels wide and wider than it is tall.',
                    ['%width%' => self::BANNER_MINIMUM_WIDTH],
                ),
                'required' => null === $revision?->getBannerLogo(),
                'mapped' => false,
                'constraints' => $this->logoConstraints(
                    $revision?->getBannerLogo(),
                    new Image(
                        maxSize: self::MAXIMUM_SIZE,
                        mimeTypes: self::MIME_TYPES,
                        mimeTypesMessage: 'Upload a JPEG, PNG or WebP image.',
                        minWidth: self::BANNER_MINIMUM_WIDTH,
                        maxRatio: self::BANNER_MAXIMUM_RATIO,
                        minRatio: self::BANNER_MINIMUM_RATIO,
                    ),
                ),
            ],
        );
    }

    /**
     * @return list<Constraint>
     */
    private function logoConstraints(
        ?string $stored,
        Image $image,
    ): array {
        if (null !== $stored) {
            return [$image];
        }

        return [
            new NotNull(message: 'Choose an image to upload.'),
            $image,
        ];
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
