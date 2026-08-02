<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyJobPackage;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Form\Application\RequiresEnabledLanguagesTrait;
use App\Repository\Career\CompanyJobPackageRepository;
use App\Repository\Career\VacancyRepository;
use App\Util\Application\SlugRule;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

use function is_string;
use function Symfony\Component\Translation\t;

/**
 * Create/edit form for a vacancy. All revisable content lives on the embedded {@see VacancyRevisionType} bound to the
 * vacancy's working revision; this root form owns the slug and the job package it is sold under, and runs the checks
 * that need both at once.
 *
 * The same form serves both audiences. Whether a vacancy is shown at all is the board's call, so that flag only exists
 * when `admin` is set, and so are the slug and the package once the vacancy is live, because neither goes through
 * review. The package choice is narrowed to the company's own running job packages unless `admin` is set.
 *
 * @extends AbstractType<Vacancy>
 */
class VacancyType extends AbstractType
{
    use RequiresEnabledLanguagesTrait;

    public function __construct(
        private readonly VacancyRepository $vacancyRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $company = $options['company'];

        // A package that has already run out is not on offer any more, but the one a vacancy was sold under has to
        // stay choosable: without it the select comes up empty on edit and the only way to save is to move the
        // vacancy to somebody else's contract.
        $data = $builder->getData();
        $vacancy = $data instanceof Vacancy
            ? $data
            : null;
        $currentPackage = null !== $vacancy
            && null !== $vacancy->getId()
                ? $vacancy->getPackage()
                : null;

        // The slug and the package sit on the vacancy rather than on a revision, so a change to either takes effect
        // the moment it is saved instead of when the committee agrees to it. A company still sets them while nothing
        // of the vacancy is public yet; once it is live, where it is reached and which contract it hangs off are the
        // board's to change.
        if (
            true === $options['admin']
            || null === $vacancy?->getLiveRevision()
        ) {
            $builder
                ->add(
                    'slugName',
                    TextType::class,
                    [
                        'label' => t('Slug'),
                        'help' => t('Identifies the vacancy in its web address, within its company and category.'),
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
                    'package',
                    EntityType::class,
                    [
                        'label' => t('Job package'),
                        'class' => CompanyJobPackage::class,
                        'choice_label' => static function (CompanyJobPackage $package): string {
                            return $package->getCompany()->getName()
                                . ' (' . $package->getExpirationDate()->format('Y-m-d') . ')';
                        },
                        'query_builder' => static function (
                            CompanyJobPackageRepository $repository,
                        ) use (
                            $company,
                            $currentPackage,
                        ): QueryBuilder {
                            $qb = $repository->createQueryBuilder('p');

                            if (null === $currentPackage) {
                                $qb->andWhere('p.expires > CURRENT_DATE()');
                            } else {
                                $qb->andWhere($qb->expr()->orX(
                                    'p.expires > CURRENT_DATE()',
                                    'p.id = :current',
                                ))
                                    ->setParameter(
                                        'current',
                                        $currentPackage->getId(),
                                        Types::INTEGER,
                                    );
                            }

                            if (null !== $company) {
                                $qb->andWhere('p.company = :company')
                                    ->setParameter(
                                        'company',
                                        $company->getId(),
                                    );
                            }

                            return $qb;
                        },
                        'constraints' => [new NotBlank(message: 'Choose the job package this vacancy belongs to.')],
                    ],
                );
        }

        if (true === $options['admin']) {
            $builder->add(
                'published',
                CheckboxType::class,
                [
                    'label' => t('Show this vacancy on the website'),
                    'required' => false,
                ],
            );
        }

        $builder->add(
            'currentRevision',
            VacancyRevisionType::class,
            ['label' => false],
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->validateLanguagesAndWindow(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vacancy::class,
            'admin' => false,
            'company' => null,
        ]);
        $resolver->setAllowedTypes(
            'admin',
            'bool',
        );
        $resolver->setAllowedTypes(
            'company',
            [
                Company::class,
                'null',
            ],
        );
    }

    private function validateLanguagesAndWindow(FormEvent $event): void
    {
        $form = $event->getForm();
        $revision = $form->get('currentRevision');

        $this->requireAtLeastOneLanguage(
            $revision,
            $this->translator,
        );
        $this->requireEnabledLanguages(
            $revision,
            [
                'name',
                'location',
                'website',
                'description',
            ],
            $this->translator,
        );

        $startForm = $revision->get('startDate');
        $endForm = $revision->get('endDate');
        $startDate = $startForm->getData();
        $endDate = $endForm->getData();

        if (
            $startDate instanceof DateTime
            && $endDate instanceof DateTime
            && $endDate < $startDate
        ) {
            $endForm->addError(new FormError(
                $this->translator->trans(
                    'The vacancy cannot close before it opens.',
                    [],
                    'validators',
                ),
            ));
        }

        // The package is only on the form while it may still be chosen; otherwise the checks that need it read the
        // one the vacancy already hangs off.
        $vacancy = $form->getData();
        $package = $form->has('package')
            ? $form->get('package')->getData()
            : ($vacancy instanceof Vacancy ? $vacancy->getPackage() : null);

        if (!$package instanceof CompanyJobPackage) {
            return;
        }

        $this->requireFreeSlug(
            $form,
            $revision,
            $package,
        );

        // A vacancy is invisible once its package expires whatever its own window says, so a window that runs past the
        // package would promise something it cannot keep. A package is already gone on the day it expires while a
        // vacancy is still open on its closing day, so the two dates cannot be the same either.
        if (
            !$endDate instanceof DateTime
            || $endDate < $package->getExpirationDate()
        ) {
            return;
        }

        $endForm->addError(new FormError(
            $this->translator->trans(
                'The vacancy cannot stay open past the job package it belongs to.',
                [],
                'validators',
            ),
        ));
    }

    /**
     * A vacancy is reached through its company and the category it sits in, so its slug only has to be free within
     * that pair. No single index holds that shape, which is why the database does not settle it and this does.
     *
     * The pair can also be broken by moving the vacancy to another category while the slug stays put, so this runs
     * whether or not the slug itself is on the form; the complaint then lands on the field that did change.
     *
     * @param FormInterface<mixed> $form
     * @param FormInterface<mixed> $revision
     */
    private function requireFreeSlug(
        FormInterface $form,
        FormInterface $revision,
        CompanyJobPackage $package,
    ): void {
        $vacancy = $form->getData();
        $vacancy = $vacancy instanceof Vacancy
            ? $vacancy
            : null;
        $categoryForm = $revision->get('category');
        $category = $categoryForm->getData();

        $slugForm = $form->has('slugName')
            ? $form->get('slugName')
            : null;
        $slug = $slugForm?->getData() ?? $vacancy?->getSlugName();

        if (
            !is_string($slug)
            || !$category instanceof VacancyCategories
            || $this->vacancyRepository->isSlugNameUnique(
                $package->getCompany(),
                $slug,
                $category,
                $vacancy,
            )
        ) {
            return;
        }

        ($slugForm ?? $categoryForm)->addError(new FormError(
            $this->translator->trans(
                'Another vacancy of this company already uses this slug in this category.',
                [],
                'validators',
            ),
        ));
    }
}
