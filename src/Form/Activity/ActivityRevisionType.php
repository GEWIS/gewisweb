<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\ActivityLabel;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Application\Enums\Languages;
use App\Entity\Career\Company;
use App\Entity\Decision\Organ;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Application\LocalisedTextType;
use App\Form\DisablesFieldsTrait;
use App\Repository\Decision\OrganRepository;
use DateTime;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_values;
use function Symfony\Component\Translation\t;
use function trim;

/**
 * The revisable content of an activity (one {@see ActivityRevision}): the localised texts, the schedule, the category,
 * the organising organ and company, the labels, the facility flags and the sign-up lists. Everything here is staged
 * with the revision and only goes live on approval, so each change is reviewed; {@see ActivityType} merely embeds this
 * form on the activity's working revision.
 *
 * The `languageDutch` / `languageEnglish` checkboxes are unmapped: they only drive the `localised-fields` Stimulus
 * controller, which enables the Dutch respectively English variant of every localised field. A disabled variant is not
 * submitted, so an unchecked language keeps whatever it already had. Their initial state is primed from the revision's
 * existing content (and a brand-new activity defaults to English).
 *
 * @extends AbstractType<ActivityRevision>
 */
class ActivityRevisionType extends AbstractType
{
    use DisablesFieldsTrait;

    /**
     * Schedule fields that become read-only once the activity has started: only the start. The end stays editable
     * while the activity is running (so a running activity can be extended); it is never separately locked because the
     * whole activity becomes immutable once it has ended (see the controller's "already took place" block). Changing
     * the start afterwards is a (highly exceptional) database-level edit.
     */
    private const array LOCK_AFTER_START = ['beginTime'];

    public function __construct(
        private readonly Security $security,
        private readonly OrganRepository $organRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $language = Languages::current();

        // Resolved once and shared between the field's initial choices and keepBoundOrgan() below, so a render issues
        // the underlying findActive() query a single time.
        $selectableOrgans = $this->selectableOrgans();

        // The localised text fields carry no label of their own; the create/edit template lays them out in mirrored
        // Dutch/English columns and labels each row by field name there.
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
                ['label' => false],
            )
            ->add(
                'beginTime',
                DateTimeType::class,
                [
                    'label' => t('Start'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter a start date and time.')],
                ],
            )
            ->add(
                'endTime',
                DateTimeType::class,
                [
                    'label' => t('End'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter an end date and time.')],
                ],
            )
            ->add(
                'location',
                LocalisedTextType::class,
                ['label' => false],
            )
            ->add(
                'costs',
                LocalisedTextType::class,
                ['label' => false],
            )
            ->add(
                'description',
                LocalisedTextType::class,
                [
                    'label' => false,
                    'multiline' => true,
                ],
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label' => t('Category'),
                    'class' => ActivityCategories::class,
                    'choices' => ActivityCategories::selectableCases(),
                ],
            )
            ->add(
                'organ',
                EntityType::class,
                // Restricted to the organs the user may organise for; a co-owner's out-of-reach current organ is kept
                // in the list by keepBoundOrgan() so a save never silently drops it.
                $this->organFieldOptions($selectableOrgans),
            )
            ->add(
                'company',
                EntityType::class,
                [
                    'label' => t('Organising company'),
                    'class' => Company::class,
                    'choice_label' => 'name',
                    'required' => false,
                    'placeholder' => t('No organising company'),
                    // Attaching an organising company is C4/board-only; a disabled field renders read-only and keeps
                    // whatever it already had on submit (so a regular member's edit never changes it).
                    'disabled' => !$this->security->isGranted(UserRoles::CompanyAdmin->value),
                ],
            )
            ->add(
                'labels',
                EntityType::class,
                [
                    'label' => t('Labels'),
                    'class' => ActivityLabel::class,
                    'choice_label' => static function (ActivityLabel $label) use ($language): string {
                        return $label->getName()->getText($language) ?? '';
                    },
                    'multiple' => true,
                    'expanded' => true,
                    'by_reference' => false,
                    'required' => false,
                ],
            )
            ->add(
                'requireGEFLITST',
                CheckboxType::class,
                [
                    'label' => t('This activity needs a GEFLITST member to take photos'),
                    'help' => t(
                        'When this is checked, GEFLITST will be notified that this activity needs a photographer.',
                    ),
                    'required' => false,
                ],
            )
            ->add(
                'requireZettle',
                CheckboxType::class,
                [
                    'label' => t('This activity needs a Zettle for payments'),
                    'help' => t(
                        'When this is checked, the treasurer will be notified that this activity needs a Zettle.',
                    ),
                    'required' => false,
                ],
            )
            ->add(
                'signupLists',
                CollectionType::class,
                [
                    'label' => t('Sign-up lists'),
                    'entry_type' => SignupListType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    'prototype_name' => '__list__',
                    // Render each list as a collapsible panel (see the `signup_list_collection` form theme); the nested
                    // field/option collections keep the generic `collection` theme.
                    'block_prefix' => 'signup_list_collection',
                ],
            );

        // The organ the revision already carries when editing starts. keepBoundOrgan() lets an out-of-reach co-owner
        // organ survive a save, so guardOrganAssignment() must recognise it as allowed; captured here by reference.
        $boundOrganId = null;
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($selectableOrgans, &$boundOrganId): void {
                $this->keepBoundOrgan(
                    $event,
                    $selectableOrgans,
                );

                $revision = $event->getData();
                $boundOrganId = $revision instanceof ActivityRevision
                    ? $revision->getOrgan()?->getId()
                    : null;
            },
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->primeLanguageToggles(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->disableScheduleWhenStarted(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($selectableOrgans, &$boundOrganId): void {
                $this->guardOrganAssignment(
                    $event,
                    $selectableOrgans,
                    $boundOrganId,
                );
            },
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ActivityRevision::class]);
    }

    /**
     * The organs the current user may assign as the organiser: every active organ for the board, otherwise only the
     * organs the user is currently installed in (mirroring {@see \App\Security\Application\RevisionVoter}'s rule).
     *
     * @return Organ[]
     */
    private function selectableOrgans(): array
    {
        if ($this->security->isGranted(UserRoles::Board->value)) {
            return $this->organRepository->findActive();
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $organs = [];
        foreach ($user->getMember()->getCurrentOrganInstallations() as $installation) {
            $organ = $installation->getOrgan();
            $organs[$organ->getId()] = $organ;
        }

        return array_values($organs);
    }

    /**
     * Options for the organising-organ field, with the assignable {@see Organ}s as its choices.
     *
     * @param Organ[] $choices
     *
     * @return array<string, mixed>
     */
    private function organFieldOptions(array $choices): array
    {
        return [
            'label' => t('Organising organ'),
            'class' => Organ::class,
            'choice_label' => 'abbr',
            'required' => false,
            'placeholder' => t('No organising organ'),
            'choices' => $choices,
        ];
    }

    /**
     * Editing must never silently drop an organ the user cannot otherwise pick (e.g. the creator of an activity whose
     * organising organ they are no longer installed in): keep the revision's current organ in the choice list so a
     * save preserves it.
     *
     * @param Organ[] $selectableOrgans the choices already wired onto the field in buildForm()
     */
    private function keepBoundOrgan(
        FormEvent $event,
        array $selectableOrgans,
    ): void {
        $revision = $event->getData();
        if (!$revision instanceof ActivityRevision) {
            return;
        }

        $organ = $revision->getOrgan();
        if (null === $organ) {
            return;
        }

        $choices = $selectableOrgans;
        foreach ($choices as $choice) {
            if ($choice->getId() === $organ->getId()) {
                return;
            }
        }

        $choices[] = $organ;
        $event->getForm()->add(
            'organ',
            EntityType::class,
            $this->organFieldOptions($choices),
        );
    }

    /**
     * Defence in depth for the authorisation invariant that an activity's organ
     * ({@see \App\Entity\Activity\Activity::getResourceOrgan()}, read straight from the working revision) is only ever
     * one the editor may organise for. The organ field already limits its choices to {@see selectableOrgans()} (plus
     * the organ the revision already had, kept by {@see keepBoundOrgan()}) and the EntityType validates a submission
     * against that list. However, because the chosen organ becomes an edit-rights anchor the moment it is saved,
     * re-assert it here instead of trusting the field configuration: reject a submitted organ that is neither
     * selectable nor the one already bound.
     *
     * @param Organ[]  $selectableOrgans the organs offered to this user, resolved once in buildForm()
     * @param int|null $boundOrganId     the organ the revision carried before this submit, allowed to survive
     */
    private function guardOrganAssignment(
        FormEvent $event,
        array $selectableOrgans,
        ?int $boundOrganId,
    ): void {
        $revision = $event->getData();
        if (!$revision instanceof ActivityRevision) {
            return;
        }

        $organ = $revision->getOrgan();
        if (null === $organ) {
            return;
        }

        $organId = $organ->getId();
        if ($organId === $boundOrganId) {
            return;
        }

        foreach ($selectableOrgans as $selectable) {
            if ($selectable->getId() === $organId) {
                return;
            }
        }

        $event->getForm()->get('organ')->addError(new FormError(
            $this->translator->trans(
                'You may only assign an organising organ that you are installed in.',
                [],
                'validators',
            ),
        ));
    }

    /**
     * Pre-check the language toggles based on which languages the revision already has content for. A brand-new
     * activity (no content in either language) defaults to English enabled so the form is immediately usable.
     */
    private function primeLanguageToggles(FormEvent $event): void
    {
        $revision = $event->getData();
        if (!$revision instanceof ActivityRevision) {
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
     * Re-add the start field as `disabled` once the activity has started, so it renders read-only and is ignored on
     * submit (the end stays editable; see {@see self::LOCK_AFTER_START}). "Started" is read from the *live* revision
     * (the real schedule), never the editable draft, so a brand-new draft that has never been published (no live
     * revision) keeps its start editable: a draft whose date slipped into the past while it sat in the review queue
     * can be moved forward and resubmitted instead of becoming un-submittable.
     */
    private function disableScheduleWhenStarted(FormEvent $event): void
    {
        $revision = $event->getData();
        if (!$revision instanceof ActivityRevision) {
            return;
        }

        // Only a genuinely live, under-way activity has its start locked; a never-published draft stays editable.
        $live = $revision->getActivity()->getLiveRevision();
        if (
            null === $live
            || $live === $revision
        ) {
            return;
        }

        $beginTime = $live->getBeginTime();
        if (
            null === $beginTime
            || $beginTime > new DateTime()
        ) {
            return;
        }

        $form = $event->getForm();
        foreach (self::LOCK_AFTER_START as $name) {
            $this->disableField(
                $form,
                $name,
            );
        }
    }

    /**
     * Whether any of the revision's localised fields has non-empty content in the given language.
     */
    private function hasContent(
        ActivityRevision $revision,
        bool $dutch,
    ): bool {
        $texts = [
            $revision->getName(),
            $revision->getLocation(),
            $revision->getCosts(),
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
