<?php

declare(strict_types=1);

namespace App\Form\Career;

use App\Entity\Application\Enums\Languages;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyHighlightPackage;
use App\Entity\Career\Vacancy;
use App\Repository\Career\VacancyRepository;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function in_array;
use function Symfony\Component\Translation\t;

/**
 * Which of a company's vacancies go on the career landing page. The choices are worked out from what the company has
 * running at this moment, and checked again when the form comes back: the two are separated by however long the page
 * sat open, and in between a vacancy can close or be taken down.
 *
 * There is deliberately no cap per category. A company that has paid for the package is trusted to know what it wants
 * shown.
 *
 * @extends AbstractType<CompanyHighlightPackage>
 */
class HighlightSelectionType extends AbstractType
{
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
        $language = Languages::current();
        $eligible = $this->vacancyRepository->findHighlightableForCompany($options['company']);

        $builder->add(
            'vacancies',
            EntityType::class,
            [
                'label' => t('Vacancies to highlight'),
                'class' => Vacancy::class,
                'choices' => $eligible,
                'choice_label' => static function (Vacancy $vacancy) use ($language): string {
                    return $vacancy->getName()->getText($language) ?? $vacancy->getSlugName();
                },
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'required' => false,
            ],
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($eligible): void {
                $this->rejectIneligible(
                    $event,
                    $eligible,
                );
            },
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CompanyHighlightPackage::class]);
        $resolver->setRequired('company');
        $resolver->setAllowedTypes(
            'company',
            Company::class,
        );
    }

    /**
     * Re-assert the eligibility the choice list expresses, because what is eligible is worked out fresh on every
     * request and a submission is answering a list that was drawn a while ago.
     *
     * @param list<Vacancy> $eligible
     */
    private function rejectIneligible(
        FormEvent $event,
        array $eligible,
    ): void {
        $package = $event->getData();
        if (!$package instanceof CompanyHighlightPackage) {
            return;
        }

        foreach ($package->getVacancies() as $vacancy) {
            if (
                in_array(
                    $vacancy,
                    $eligible,
                    true,
                )
            ) {
                continue;
            }

            $event->getForm()->get('vacancies')->addError(new FormError(
                $this->translator->trans(
                    'You can only highlight your own vacancies that are currently shown on the website.',
                    [],
                    'validators',
                ),
            ));

            return;
        }
    }
}
