<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\Enums\DrawCutoffRule;
use App\Entity\Activity\SignupList;
use App\Form\Application\LocalisedTextType;
use App\Form\DisablesFieldsTrait;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function array_keys;
use function Symfony\Component\Translation\t;
use function trim;

/**
 * A sign-up list ({@see SignupList}) with its custom fields. Once the list has sign-ups its structure is frozen:
 * everything except the safe metadata (name, closing date, visibility of the counter, promotion) is disabled, so an
 * already-committed list can never be structurally changed and existing sign-ups stay valid.
 *
 * @extends AbstractType<SignupList>
 */
class SignupListType extends AbstractType
{
    use DisablesFieldsTrait;

    /**
     * Fields disabled once a list has sign-ups.
     */
    private const array FROZEN_FIELDS = [
        'openDate',
        'onlyGEWIS',
        'limitedCapacity',
        'fields',
    ];

    /**
     * The allocation method and its per-method settings; disabled once the list's draw has been performed (the draw
     * allocated places under exactly these settings, so changing them afterwards would leave the lock stale).
     */
    private const array ALLOCATION_FIELDS = [
        'allocationMethod',
        'capacity',
        'drawCutoffRule',
        'drawCutoffAt',
        'drawAfterDurationHours',
        'externalPolicyUrl',
        'externalForceOrdering',
        'externalPaymentByExternal',
        'customMethodDescription',
    ];

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'name',
                LocalisedTextType::class,
                ['label' => t('Name')],
            )
            ->add(
                'openDate',
                DateTimeType::class,
                [
                    'label' => t('Opens'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter an opening date and time.')],
                    // The entity setter is non-nullable; an empty submission maps to null and would TypeError during
                    // data mapping (before NotBlank runs). Skip the write when empty so NotBlank reports it instead.
                    'setter' => static function (SignupList $list, ?DateTime $value): void {
                        if (null === $value) {
                            return;
                        }

                        $list->setOpenDate($value);
                    },
                ],
            )
            ->add(
                'closeDate',
                DateTimeType::class,
                [
                    'label' => t('Closes'),
                    'widget' => 'single_text',
                    'constraints' => [new NotBlank(message: 'Enter a closing date and time.')],
                    'setter' => static function (SignupList $list, ?DateTime $value): void {
                        if (null === $value) {
                            return;
                        }

                        $list->setCloseDate($value);
                    },
                ],
            )
            ->add(
                'onlyGEWIS',
                CheckboxType::class,
                [
                    'label' => t('Members only'),
                    'required' => false,
                ],
            )
            ->add(
                'displaySubscribedNumber',
                CheckboxType::class,
                [
                    'label' => t('Show the number of sign-ups to logged-out visitors'),
                    'required' => false,
                ],
            )
            ->add(
                'limitedCapacity',
                CheckboxType::class,
                [
                    'label' => t('Limited capacity'),
                    'required' => false,
                ],
            )
            // The capacity is required (and validated) only when "limited capacity" is checked; see the form-level
            // Callback in configureOptions(). Deliberately not in FROZEN_FIELDS so the number stays editable in a
            // revision even after the list has sign-ups.
            ->add(
                'capacity',
                IntegerType::class,
                [
                    'label' => t('Capacity'),
                    'required' => false,
                ],
            )
            // Allocation method + its per-method settings; only meaningful when limited, and validated conditionally
            // by the form-level Callback. Not frozen, like capacity.
            ->add(
                'allocationMethod',
                EnumType::class,
                [
                    'label' => t('Allocation method'),
                    'class' => AllocationMethod::class,
                ],
            )
            ->add(
                'drawCutoffRule',
                EnumType::class,
                [
                    'label' => t('When to draw'),
                    'class' => DrawCutoffRule::class,
                    'required' => false,
                    'placeholder' => t('Choose when the draw happens'),
                ],
            )
            ->add(
                'drawCutoffAt',
                DateTimeType::class,
                [
                    'label' => t('Draw cutoff moment'),
                    'widget' => 'single_text',
                    'required' => false,
                ],
            )
            ->add(
                'drawAfterDurationHours',
                IntegerType::class,
                [
                    'label' => t('Draw after being open for (hours)'),
                    'required' => false,
                ],
            )
            ->add(
                'externalPolicyUrl',
                UrlType::class,
                [
                    'label' => t('External party policy URL'),
                    'required' => false,
                    'constraints' => [
                        new Url(
                            message: 'Enter a valid URL (starting with http:// or https://).',
                            protocols: [
                                'http',
                                'https',
                            ],
                        ),
                    ],
                ],
            )
            ->add(
                'externalForceOrdering',
                CheckboxType::class,
                [
                    'label' => t('The external party dictates the order of admissions'),
                    'required' => false,
                ],
            )
            ->add(
                'externalPaymentByExternal',
                CheckboxType::class,
                [
                    'label' => t('Payment is collected by the external party'),
                    'required' => false,
                ],
            )
            ->add(
                'customMethodDescription',
                TextareaType::class,
                [
                    'label' => t('Describe the allocation method'),
                    'required' => false,
                ],
            )
            ->add(
                'promoted',
                CheckboxType::class,
                [
                    'label' => t('Promoted'),
                    'required' => false,
                ],
            )
            ->add(
                'fields',
                CollectionType::class,
                [
                    'label' => false,
                    'entry_type' => SignupFieldType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    'prototype_name' => '__field__',
                    // Render each question as a collapsible panel (see the `signup_field_collection` form theme).
                    'block_prefix' => 'signup_field_collection',
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->freezeWhenActivityStarted(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->freezeWhenSubscribed(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->freezeWhenDrawn(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->disableOpenDateWhenOpened(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->disableCloseDateWhenClosed(...),
        );
        // After binding, drop any per-method settings that do not apply to the chosen method (or to an unlimited
        // list), so hidden inputs cannot persist stale config that the cloner would carry into future revisions.
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->clearInapplicableAllocation(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SignupList::class,
            // A limited-capacity list must carry a positive capacity; validated at the object level so the rule can
            // depend on the limitedCapacity flag.
            'constraints' => [new Callback($this->validateCapacity(...))],
        ]);
    }

    public function validateCapacity(
        mixed $list,
        ExecutionContextInterface $context,
    ): void {
        if (
            !$list instanceof SignupList
            || !$list->getLimitedCapacity()
        ) {
            return;
        }

        $capacity = $list->getCapacity();
        if (
            null === $capacity
            || $capacity < 1
        ) {
            $context->buildViolation('Enter a capacity of at least 1 for a limited-capacity list.')
                ->atPath('capacity')
                ->addViolation();
        }

        $this->validateAllocationMethod(
            $list,
            $context,
        );
    }

    /**
     * Require the settings the selected allocation method needs (only reached for a limited-capacity list).
     */
    private function validateAllocationMethod(
        SignupList $list,
        ExecutionContextInterface $context,
    ): void {
        switch ($list->getAllocationMethod()) {
            case AllocationMethod::ConditionalDraw:
                $rule = $list->getDrawCutoffRule();
                if (null === $rule) {
                    $context->buildViolation('Choose when the draw happens.')
                        ->atPath('drawCutoffRule')
                        ->addViolation();

                    break;
                }

                if (
                    DrawCutoffRule::IfFullBefore === $rule
                    && null === $list->getDrawCutoffAt()
                ) {
                    $context->buildViolation('Enter the moment the list must be full by.')
                        ->atPath('drawCutoffAt')
                        ->addViolation();
                }

                if (
                    DrawCutoffRule::AfterDurationOpen === $rule
                    && (
                        null === $list->getDrawAfterDurationHours()
                        || $list->getDrawAfterDurationHours() < 1
                    )
                ) {
                    $context->buildViolation('Enter a positive number of hours.')
                        ->atPath('drawAfterDurationHours')
                        ->addViolation();
                }

                break;
            case AllocationMethod::ExternalParty:
                if ('' === trim($list->getExternalPolicyUrl() ?? '')) {
                    $context->buildViolation('Enter the external party policy URL.')
                        ->atPath('externalPolicyUrl')
                        ->addViolation();
                }

                break;
            case AllocationMethod::Custom:
                if ('' === trim($list->getCustomMethodDescription() ?? '')) {
                    $context->buildViolation('Describe the allocation method.')
                        ->atPath('customMethodDescription')
                        ->addViolation();
                }

                break;
            case AllocationMethod::FirstComeFirstServed:
                break;
        }
    }

    /**
     * Expose whether the list is structurally frozen (it, or its live lineage counterpart, has sign-ups) so the
     * collection theme can hide the "remove" button: a list with sign-ups must never be deleted.
     *
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options,
    ): void {
        $list = $form->getData();
        $view->vars['frozen'] = $this->hasLiveSignUps($list)
            || $this->activityStarted($list);
    }

    /**
     * Re-add every field as `disabled` once the activity has started, so a started activity's sign-up lists can no
     * longer be changed in any way (the `fields` collection too, which cascades to its nested questions/options). A
     * brand-new list, or one whose activity is still upcoming, stays editable.
     */
    private function freezeWhenActivityStarted(FormEvent $event): void
    {
        $list = $event->getData();
        if (
            !$list instanceof SignupList
            || !$this->activityStarted($list)
        ) {
            return;
        }

        $form = $event->getForm();
        foreach (array_keys($form->all()) as $name) {
            $this->disableField(
                $form,
                $name,
            );
        }
    }

    /**
     * Whether the live activity this list belongs to has already started. "Started" is read from the *live* revision
     * (the real schedule): a brand-new list whose activity has never been published has no live revision and so is
     * never considered started, keeping it editable so a stale draft can be re-dated. Mirrors
     * {@see \App\Form\Activity\ActivityRevisionType} locking the start once the live activity has begun.
     */
    private function activityStarted(?SignupList $list): bool
    {
        if (
            !$list instanceof SignupList
            || !$list->hasRevision()
        ) {
            return false;
        }

        $live = $list->getRevision()->getActivity()->getLiveRevision();
        if (null === $live) {
            return false;
        }

        $begin = $live->getBeginTime();

        return null !== $begin && $begin <= new DateTime();
    }

    /**
     * Re-add the structural fields as `disabled` for a list that already has sign-ups, so they render read-only and
     * are ignored on submit.
     */
    private function freezeWhenSubscribed(FormEvent $event): void
    {
        $list = $event->getData();
        if (
            !$list instanceof SignupList
            || !$this->hasLiveSignUps($list)
        ) {
            return;
        }

        $form = $event->getForm();
        foreach (self::FROZEN_FIELDS as $name) {
            $this->disableField(
                $form,
                $name,
            );
        }
    }

    /**
     * Whether this list, or (when it is a draft clone) the live revision's list it descends from, has sign-ups. A
     * draft clone's own sign-ups are always empty (sign-ups live on the live revision until approval migrates them),
     * so the structural freeze must look through the lineage to the live counterpart. The collection prototype has no
     * bound list (`null`), in which case nothing is frozen.
     */
    private function hasLiveSignUps(?SignupList $list): bool
    {
        return $this->holdsForLiveLineage(
            $list,
            static fn (SignupList $candidate): bool => $candidate->hasSignUps(),
        );
    }

    /**
     * The shared lineage walk behind {@see self::hasLiveSignUps()} and {@see self::isLiveDrawn()}: whether a predicate
     * holds for this list, or (when it is a draft clone whose own state is still empty because sign-ups/draws live
     * on the live revision until approval) for the live revision's list it descends from (matched by
     * {@see SignupList::getLineageId()}). The collection prototype has no bound list (`null`), for which this is false.
     *
     * @param callable(SignupList): bool $predicate
     */
    private function holdsForLiveLineage(
        ?SignupList $list,
        callable $predicate,
    ): bool {
        if (!$list instanceof SignupList) {
            return false;
        }

        if ($predicate($list)) {
            return true;
        }

        if (!$list->hasRevision()) {
            return false;
        }

        $live = $list->getRevision()->getActivity()->getLiveRevision();
        if (null === $live) {
            return false;
        }

        foreach ($live->getSignupLists() as $liveList) {
            if (
                $liveList->getLineageId()->equals($list->getLineageId())
                && $predicate($liveList)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Re-add the allocation fields as `disabled` once the list's draw has been performed, so an already-drawn list's
     * method/capacity/settings can no longer be changed (which would leave the carried draw lock stale).
     */
    private function freezeWhenDrawn(FormEvent $event): void
    {
        $list = $event->getData();
        if (
            !$list instanceof SignupList
            || !$this->isLiveDrawn($list)
        ) {
            return;
        }

        $form = $event->getForm();
        foreach (self::ALLOCATION_FIELDS as $name) {
            $this->disableField(
                $form,
                $name,
            );
        }
    }

    /**
     * Whether this list, or its live lineage counterpart, has had its draw performed (and locked). Like
     * {@see self::hasLiveSignUps()} a draft clone carries the lock forward, so the same lineage walk is used.
     */
    private function isLiveDrawn(?SignupList $list): bool
    {
        return $this->holdsForLiveLineage(
            $list,
            static fn (SignupList $candidate): bool => $candidate->isDrawLocked(),
        );
    }

    /**
     * Null out the per-method settings that do not apply to the bound list's final state, so an unlimited list (or one
     * whose method changed) cannot persist stale allocation config that could later reappear or be cloned. Skipped
     * for an already-drawn list, whose allocation fields are frozen (not submitted) and must keep their values.
     */
    private function clearInapplicableAllocation(FormEvent $event): void
    {
        $list = $event->getData();
        if (
            !$list instanceof SignupList
            || $this->isLiveDrawn($list)
        ) {
            return;
        }

        if (!$list->getLimitedCapacity()) {
            $list->setCapacity(null);
            $list->setAllocationMethod(AllocationMethod::FirstComeFirstServed);
        }

        $method = $list->getAllocationMethod();

        if (
            AllocationMethod::ConditionalDraw !== $method
            || DrawCutoffRule::IfFullBefore !== $list->getDrawCutoffRule()
        ) {
            $list->setDrawCutoffAt(null);
        }

        if (
            AllocationMethod::ConditionalDraw !== $method
            || DrawCutoffRule::AfterDurationOpen !== $list->getDrawCutoffRule()
        ) {
            $list->setDrawAfterDurationHours(null);
        }

        if (AllocationMethod::ConditionalDraw !== $method) {
            $list->setDrawCutoffRule(null);
        }

        if (AllocationMethod::ExternalParty !== $method) {
            $list->setExternalPolicyUrl(null);
            $list->setExternalForceOrdering(false);
            $list->setExternalPaymentByExternal(false);
        }

        if (AllocationMethod::Custom === $method) {
            return;
        }

        $list->setCustomMethodDescription(null);
    }

    /**
     * Re-add `openDate` as `disabled` once a (persisted) sign-up list has opened, so a passed opening date can no
     * longer be moved; only a newly-set opening date must be in the future. A brand-new list (no id yet) is always
     * editable.
     */
    private function disableOpenDateWhenOpened(FormEvent $event): void
    {
        $list = $event->getData();
        if (
            !$list instanceof SignupList
            || !$list->hasRevision()
            || $list->getOpenDate() > new DateTime()
        ) {
            return;
        }

        $this->disableField(
            $event->getForm(),
            'openDate',
        );
    }

    /**
     * Re-add `closeDate` as `disabled` once a (persisted) sign-up list has closed, so a passed closing date can no
     * longer be moved. While the list is still open or upcoming the closing date stays editable, so it can be
     * extended; a brand-new list (no id yet) is always editable.
     */
    private function disableCloseDateWhenClosed(FormEvent $event): void
    {
        $list = $event->getData();
        if (
            !$list instanceof SignupList
            || !$list->hasRevision()
            || $list->getCloseDate() > new DateTime()
        ) {
            return;
        }

        $this->disableField(
            $event->getForm(),
            'closeDate',
        );
    }
}
