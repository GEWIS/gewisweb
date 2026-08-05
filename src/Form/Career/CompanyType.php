<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Career\Company;
use App\Form\Application\RequiresEnabledLanguagesTrait;
use App\Util\Application\SlugRule;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\Translation\t;

/**
 * Create/edit form for a company. All revisable content lives on the embedded {@see CompanyRevisionType} bound to the
 * company's working revision; this root form owns what survives across revisions and runs the language validation that
 * needs the whole revision at once.
 *
 * The same form serves both audiences. The name, the slug and the publication flag decide how a company is identified
 * and whether it appears at all, which is the board's call rather than the company's, so those fields only exist when
 * `admin` is set. A representative editing its own profile simply does not get them, and a submission that names them
 * anyway has nowhere to land.
 *
 * @extends AbstractType<Company>
 */
class CompanyType extends AbstractType
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
        if (true === $options['admin']) {
            $builder
                ->add(
                    'name',
                    TextType::class,
                    [
                        'label' => t('Name'),
                        'constraints' => [
                            new NotBlank(message: 'Enter a name.'),
                            new Length(max: 255),
                        ],
                    ],
                )
                ->add(
                    'slugName',
                    TextType::class,
                    [
                        'label' => t('Slug'),
                        'help' => t('Identifies the company in its web address.'),
                        'constraints' => [
                            new NotBlank(message: 'Enter a slug.'),
                            new Length(max: 255),
                            new Regex(
                                pattern: SlugRule::PATTERN,
                                message: 'A slug starts with a letter and contains only lowercase letters, digits, underscores and hyphens.', // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                            ),
                        ],
                    ],
                )
                ->add(
                    'published',
                    CheckboxType::class,
                    [
                        'label' => t('Show this company on the website'),
                        'required' => false,
                    ],
                );
        }

        $builder->add(
            'currentRevision',
            CompanyRevisionType::class,
            ['label' => false],
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->validateLanguages(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
            'admin' => false,
        ]);
        $resolver->setAllowedTypes(
            'admin',
            'bool',
        );
    }

    /**
     * The localised texts are required for each enabled language, and at least one language must be enabled: the
     * per-language requirements are skipped for a language that is off, so with both off a company with no content at
     * all would save.
     */
    private function validateLanguages(FormEvent $event): void
    {
        $revision = $event->getForm()->get('currentRevision');

        $this->requireAtLeastOneLanguage(
            $revision,
            $this->translator,
        );
        $this->requireEnabledLanguages(
            $revision,
            [
                'slogan',
                'website',
                'description',
            ],
            $this->translator,
        );
    }
}
