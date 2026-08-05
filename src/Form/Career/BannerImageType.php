<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Career\Enums\CompanyBannerFormats;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotNull;

use function round;
use function Symfony\Component\Translation\t;

/**
 * The image going onto a banner package, whoever puts it there: the company proposing one and the committee setting
 * one both upload through this form.
 *
 * The size the package was bought in is a hard requirement rather than something we crop towards. A banner is finished
 * artwork with a logo and a line of text on it, so anything that has to be cropped or stretched to fit is not the
 * banner that was paid for, and the company is better off being told than being surprised by the result.
 *
 * @extends AbstractType<null>
 */
final class BannerImageType extends AbstractType
{
    /**
     * The ratio the validator compares against is rounded to two decimals, so the bound the format is turned into is
     * rounded the same way.
     */
    private const int RATIO_PRECISION = 2;

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $format = $options['format'];
        $ratio = round(
            $format->width() / $format->height(),
            self::RATIO_PRECISION,
        );

        $builder->add(
            'image',
            FileType::class,
            [
                'label' => t('Image'),
                'help' => t(
                    'Upload the artwork at %width% by %height% pixels. A larger image in exactly those proportions '
                    . 'works too, and stays sharp on a high-resolution screen.',
                    [
                        '%width%' => $format->width(),
                        '%height%' => $format->height(),
                    ],
                ),
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                'constraints' => [
                    new NotNull(message: 'Choose an image to upload.'),
                    new Image(
                        maxSize: '8M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        minWidth: $format->width(),
                        minHeight: $format->height(),
                        maxRatio: $ratio,
                        minRatio: $ratio,
                    ),
                ],
            ],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('format');
        $resolver->setAllowedTypes(
            'format',
            CompanyBannerFormats::class,
        );
    }
}
