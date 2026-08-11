<?php

declare(strict_types=1);

namespace App\Form\Decision;

use App\Entity\Decision\OrganInformation;
use App\Form\Application\RequiresEnabledLanguagesTrait;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The edit form for a body's page. Everything a body writes lives on the embedded
 * {@see OrganInformationRevisionType} bound to the working revision; the page itself holds nothing editable, since a
 * body's name, abbreviation and installation all come from the decisions rather than from anybody typing them.
 *
 * That leaves this form with the language check that needs the whole revision at once.
 *
 * @extends AbstractType<OrganInformation>
 */
class OrganInformationType extends AbstractType
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
        $builder->add(
            'currentRevision',
            OrganInformationRevisionType::class,
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
        $resolver->setDefaults(['data_class' => OrganInformation::class]);
    }

    /**
     * The descriptions are required for each enabled language, and at least one language must be enabled: the
     * per-language requirements are skipped for a language that is off, so with both off a page with no text at all
     * would save.
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
                'shortDescription',
                'description',
            ],
            $this->translator,
        );
    }
}
