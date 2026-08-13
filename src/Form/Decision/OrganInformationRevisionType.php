<?php

declare(strict_types=1);

namespace App\Form\Decision;

use App\Entity\Decision\DecisionLocalisedText;
use App\Entity\Decision\OrganInformationRevision;
use App\Form\Application\CropRectangleType;
use App\Form\Application\LocalisedTextType;
use App\Form\Application\SocialLinksType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;

use function intval;
use function round;
use function Symfony\Component\Translation\t;

/**
 * What a body writes about itself on one revision of its page: the two descriptions, how to reach it, where else it can
 * be followed, and the two images it is shown by. Everything here is staged with the revision and only reaches the
 * website once the board agrees to it.
 *
 * The `languageDutch` / `languageEnglish` checkboxes are unmapped: they only drive the `localised-fields` Stimulus
 * controller, which enables the Dutch respectively English variant of every localised field. A disabled variant is not
 * submitted, so an unchecked language keeps whatever it already had.
 *
 * The image fields are unmapped as well. What is stored is the file as it arrived, and the crop that is chosen against
 * it afterwards; the controller does both, because only it can talk to storage.
 *
 * @extends AbstractType<OrganInformationRevision>
 */
class OrganInformationRevisionType extends AbstractType
{
    private const string MAXIMUM_SIZE = '8M';

    private const array MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /** What the file picker offers, kept in step with {@see self::MIME_TYPES}. */
    private const string ACCEPT = 'image/jpeg,image/png,image/webp';

    /** A banner runs the width of the page, so anything narrower than this is visibly soft. */
    public const int BANNER_MINIMUM_WIDTH = 1280;

    /** A logo is shown small, but the frame takes only a share of the upload, so it still needs some room. */
    public const int LOGO_MINIMUM_WIDTH = 640;

    /** The shape each image is cut to, which is also what the crop picker holds itself to. */
    public const float BANNER_RATIO = 4.0;

    public const float LOGO_RATIO = 16 / 9;

    /** A card has room for a line or two, and a card that ran on would break the grid it sits in. */
    private const int SHORT_DESCRIPTION_MAXIMUM = 150;

    private const int DESCRIPTION_MAXIMUM = 10000;

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $localised = [
            'label' => false,
            'data_class' => DecisionLocalisedText::class,
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
                'shortDescription',
                LocalisedTextType::class,
                $localised + ['value_constraints' => [new Length(max: self::SHORT_DESCRIPTION_MAXIMUM)]],
            )
            ->add(
                'description',
                LocalisedTextType::class,
                $localised + [
                    'multiline' => true,
                    'value_constraints' => [new Length(max: self::DESCRIPTION_MAXIMUM)],
                ],
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => t('Email address'),
                    'help' => t('Shown to signed-in members only.'),
                    'required' => false,
                    'constraints' => [
                        new Email(),
                        new Length(max: 255),
                    ],
                ],
            )
            ->add(
                'website',
                UrlType::class,
                [
                    'label' => t('Website'),
                    'required' => false,
                    'default_protocol' => 'https',
                    'constraints' => [new Length(max: 255)],
                ],
            )
            ->add(
                'socialLinks',
                SocialLinksType::class,
                [
                    // The form works in handles; turning those into rows is the revision's business, because that is
                    // the side the foreign key lives on.
                    'getter' => static fn (OrganInformationRevision $revision): array => $revision->getSocialHandles(),
                    'setter' => static function (
                        OrganInformationRevision $revision,
                        array $handles,
                    ): void {
                        $revision->updateSocialLinks($handles);
                    },
                ],
            );

        $this->addImageFields($builder);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->primeLanguageToggles(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OrganInformationRevision::class]);
    }

    /**
     * A body that already wrote something in a language keeps that language on, so opening the form does not silently
     * offer to drop half of a page.
     */
    private function primeLanguageToggles(FormEvent $event): void
    {
        $revision = $event->getData();
        $form = $event->getForm();

        if (!$revision instanceof OrganInformationRevision) {
            return;
        }

        $dutch = null !== $revision->getShortDescription()->getValueNL()
            || null !== $revision->getDescription()->getValueNL();
        $english = null !== $revision->getShortDescription()->getValueEN()
            || null !== $revision->getDescription()->getValueEN();

        // A page nobody has written yet starts with both on, which is what a body is asked for.
        if (
            !$dutch
            && !$english
        ) {
            $dutch = true;
            $english = true;
        }

        $form->get('languageDutch')->setData($dutch);
        $form->get('languageEnglish')->setData($english);
    }

    /**
     * The height that follows from a minimum width and the shape the image is cut to. An upload that is wide enough but
     * too flat holds no rectangle of that shape and that width, so asking for the width alone would let through a file
     * that has no usable crop in it at all.
     */
    private static function minimumHeight(
        int $width,
        float $ratio,
    ): int {
        return intval(round($width / $ratio));
    }

    /**
     * Unmapped: the controller stores the upload and puts the path on the revision. Neither image is ever required, so
     * a body can write its page first and find artwork later.
     *
     * @param FormBuilderInterface<OrganInformationRevision|null> $builder
     */
    private function addImageFields(FormBuilderInterface $builder): void
    {
        $bannerMinimumHeight = self::minimumHeight(
            self::BANNER_MINIMUM_WIDTH,
            self::BANNER_RATIO,
        );
        $logoMinimumHeight = self::minimumHeight(
            self::LOGO_MINIMUM_WIDTH,
            self::LOGO_RATIO,
        );

        $builder->add(
            'bannerFile',
            FileType::class,
            [
                'label' => t('Page banner'),
                'help' => t(
                    'The wide strip across the top of your own page, at least %width% by %height% pixels.',
                    [
                        '%width%' => self::BANNER_MINIMUM_WIDTH,
                        '%height%' => $bannerMinimumHeight,
                    ],
                ),
                'required' => false,
                'mapped' => false,
                // The picker offers only what the constraint below would accept, so a file that could never be stored
                // is not offered in the first place, and the width the constraint wants travels along so the frame can
                // turn away an image it would refuse. The constraints still decide: both attributes are conveniences.
                'attr' => [
                    'accept' => self::ACCEPT,
                    'data-minimum-width' => self::BANNER_MINIMUM_WIDTH,
                    'data-minimum-height' => $bannerMinimumHeight,
                ],
                'constraints' => [
                    new Image(
                        maxSize: self::MAXIMUM_SIZE,
                        mimeTypes: self::MIME_TYPES,
                        mimeTypesMessage: 'Upload a JPEG, PNG or WebP image.',
                        minWidth: self::BANNER_MINIMUM_WIDTH,
                        minHeight: $bannerMinimumHeight,
                    ),
                ],
            ],
        );

        $builder->add(
            'logoFile',
            FileType::class,
            [
                'label' => t('Logo'),
                'help' => t(
                    'What your body is recognised by on an overview card, at least %width% by %height% pixels.',
                    [
                        '%width%' => self::LOGO_MINIMUM_WIDTH,
                        '%height%' => $logoMinimumHeight,
                    ],
                ),
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => self::ACCEPT,
                    'data-minimum-width' => self::LOGO_MINIMUM_WIDTH,
                    'data-minimum-height' => $logoMinimumHeight,
                ],
                'constraints' => [
                    new Image(
                        maxSize: self::MAXIMUM_SIZE,
                        mimeTypes: self::MIME_TYPES,
                        mimeTypesMessage: 'Upload a JPEG, PNG or WebP image.',
                        minWidth: self::LOGO_MINIMUM_WIDTH,
                        minHeight: $logoMinimumHeight,
                    ),
                ],
            ],
        );

        // What the crop picker writes back: the chosen rectangle as fractions of whichever rendition it was shown,
        // which is what makes it independent of that rendition's size.
        //
        // These start out empty, and only the picker ever fills them. The crop that is in force reaches the picker
        // beside the frame instead of through here: a rectangle that is put in by hand is submitted again even when
        // nothing drew a frame, and would then be cut out of an image it was never chosen on.
        foreach (
            [
                'bannerCropData',
                'logoCropData',
            ] as $field
        ) {
            $builder->add(
                $field,
                CropRectangleType::class,
                [
                    'label' => false,
                    'mapped' => false,
                ],
            );
        }
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'organ_information_revision';
    }
}
