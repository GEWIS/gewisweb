<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\SignupList;
use App\Form\Application\LocalisedTextType;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * A sign-up list ({@see SignupList}) with its custom fields. Once the list has sign-ups its structure is frozen:
 * everything except the safe metadata (name, closing date, visibility of the counter, promotion) is disabled, so an
 * already-committed list can never be structurally changed and existing sign-ups stay valid.
 *
 * @extends AbstractType<SignupList>
 */
class SignupListType extends AbstractType
{
    /**
     * Fields disabled once a list has sign-ups.
     */
    private const array FROZEN_FIELDS = [
        'openDate',
        'onlyGEWIS',
        'limitedCapacity',
        'fields',
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
                    'label' => t('Custom fields'),
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
            $this->freezeWhenSubscribed(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->disableOpenDateWhenOpened(...),
        );
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            $this->disableCloseDateWhenClosed(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SignupList::class]);
    }

    /**
     * Expose whether the list is structurally frozen (it -- or its live lineage counterpart -- has sign-ups) so the
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
        $view->vars['frozen'] = $this->hasLiveSignUps($form->getData());
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
            $config = $form->get($name)->getConfig();
            $options = $config->getOptions();
            $options['disabled'] = true;

            $form->add(
                $name,
                $config->getType()->getInnerType()::class,
                $options,
            );
        }
    }

    /**
     * Whether this list -- or, when it is a draft clone, the live revision's list it descends from -- has sign-ups. A
     * draft clone's own sign-ups are always empty (sign-ups live on the live revision until approval migrates them),
     * so the structural freeze must look through the lineage to the live counterpart. The collection prototype has no
     * bound list (`null`), in which case nothing is frozen.
     */
    private function hasLiveSignUps(?SignupList $list): bool
    {
        if (!$list instanceof SignupList) {
            return false;
        }

        if ($list->hasSignUps()) {
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
                && $liveList->hasSignUps()
            ) {
                return true;
            }
        }

        return false;
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

        $form = $event->getForm();
        $config = $form->get('openDate')->getConfig();
        $options = $config->getOptions();
        $options['disabled'] = true;

        $form->add(
            'openDate',
            $config->getType()->getInnerType()::class,
            $options,
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

        $form = $event->getForm();
        $config = $form->get('closeDate')->getConfig();
        $options = $config->getOptions();
        $options['disabled'] = true;

        $form->add(
            'closeDate',
            $config->getType()->getInnerType()::class,
            $options,
        );
    }
}
