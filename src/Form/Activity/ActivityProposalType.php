<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\OptionPeriod;
use App\Entity\Decision\Organ;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Activity\OptionPeriodRepository;
use App\Repository\Decision\OrganRepository;
use App\Service\Activity\ProposalLimitResolver;
use DateTime;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_values;
use function in_array;
use function intval;
use function Symfony\Component\Translation\t;

/**
 * A body putting an activity forward with the days it could fall on.
 *
 * The rules that need more than one field live in a `POST_SUBMIT` listener, the way {@see ActivityType} does it, since
 * this repository has no custom constraint classes. Four of them:
 *
 *  - the round has to be taking proposals, unless the board is doing the proposing;
 *  - the body has to have room left, counting everything but the proposal being edited, or a body on its last slot
 *    could never save a change to the one that used it;
 *  - every day has to fall inside the round and run forwards, and still be ahead of us;
 *  - the body has to be one the person may act for, re-checked here because the choice list is only a list.
 *
 * The allowance is checked once more where it is written ({@see \App\Service\Activity\ActivityProposalManager}):
 * counting here and inserting later is two steps, and two people from one body can pass both.
 *
 * @extends AbstractType<ActivityProposal>
 */
class ActivityProposalType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly OrganRepository $organRepository,
        private readonly OptionPeriodRepository $optionPeriodRepository,
        private readonly ProposalLimitResolver $limitResolver,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $selectableOrgans = $this->selectableOrgans();
        $isBoard = $this->security->isGranted(UserRoles::Board->value);

        $builder
            ->add(
                'organ',
                EntityType::class,
                [
                    'label' => t('Body'),
                    'class' => Organ::class,
                    'choices' => $selectableOrgans,
                    'choice_label' => 'abbr',
                    'autocomplete' => true,
                    // Only the board may leave this empty, which is how it proposes an activity it hosts itself.
                    'required' => !$isBoard,
                    'placeholder' => $isBoard ? t('The board itself') : t('Select a body'),
                    'constraints' => $isBoard
                        ? []
                        : [new NotNull(message: 'Pick the body that would host this activity.')],
                ],
            )
            ->add(
                'period',
                EntityType::class,
                [
                    'label' => t('Round'),
                    'class' => OptionPeriod::class,
                    'choices' => $this->selectablePeriods($isBoard),
                    'choice_label' => 'name',
                    'placeholder' => t('Select a round'),
                    'constraints' => [new NotNull(message: 'Pick the round this is for.')],
                ],
            )
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Working title'),
                    'help' => t('Everyone reads this on the calendar, so make it say what the activity is.'),
                    'constraints' => [
                        new NotBlank(message: 'Give the activity a name.'),
                        new Length(
                            min: 2,
                            max: 128,
                            minMessage: 'The name should be at least {{ limit }} characters.',
                            maxMessage: 'The name should be at most {{ limit }} characters.',
                        ),
                    ],
                ],
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label' => t('Anything the board should know'),
                    'help' => t('A dependency on somebody outside the association, how big it is, why this day.'),
                    'required' => false,
                    'attr' => ['rows' => 3],
                ],
            )
            ->add(
                'dateOptions',
                CollectionType::class,
                [
                    'label' => t('Days it could be on'),
                    'entry_type' => ActivityDateOptionType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    'prototype_name' => '__option__',
                    'block_prefix' => 'date_option_collection',
                    'constraints' => [
                        new Count(
                            min: 1,
                            max: ActivityProposal::MAX_DATE_OPTIONS,
                            minMessage: 'Put forward at least one day.',
                            maxMessage: 'Put forward at most {{ limit }} days for one activity.',
                        ),
                    ],
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($selectableOrgans, $isBoard): void {
                $this->validateProposal(
                    $event,
                    $selectableOrgans,
                    $isBoard,
                );
            },
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ActivityProposal::class]);
    }

    /**
     * @param Organ[] $selectableOrgans
     */
    private function validateProposal(
        FormEvent $event,
        array $selectableOrgans,
        bool $isBoard,
    ): void {
        $proposal = $event->getData();
        $form = $event->getForm();

        if (!$proposal instanceof ActivityProposal) {
            return;
        }

        // The days are in the order the body put them in, which is its order of preference. Numbering them here keeps
        // that order without asking anybody to fill in a number.
        $position = 1;
        foreach ($proposal->getDateOptions() as $dateOption) {
            $dateOption->setPosition($position);
            ++$position;
        }

        $period = $form->get('period')->getData();

        if (!$period instanceof OptionPeriod) {
            return;
        }

        $this->guardSubmissionWindow(
            $form,
            $period,
            $isBoard,
        );
        $this->guardOrgan(
            $form,
            $proposal,
            $selectableOrgans,
            $isBoard,
        );
        $this->guardAllowance(
            $form,
            $proposal,
            $period,
            $isBoard,
        );
        $this->guardDays(
            $form,
            $period,
        );
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function guardSubmissionWindow(
        FormInterface $form,
        OptionPeriod $period,
        bool $isBoard,
    ): void {
        if (
            $isBoard
            || $period->isOpenAt(new DateTime())
        ) {
            return;
        }

        $form->get('period')->addError(new FormError(
            $this->translator->trans(
                'This round is not taking proposals at the moment.',
                [],
                'validators',
            ),
        ));
    }

    /**
     * The choice list is only a list; the body that ends up on a proposal is what anchors who may edit it afterwards,
     * so it is checked again here.
     *
     * @param FormInterface<mixed> $form
     * @param Organ[]              $selectableOrgans
     */
    private function guardOrgan(
        FormInterface $form,
        ActivityProposal $proposal,
        array $selectableOrgans,
        bool $isBoard,
    ): void {
        $organ = $proposal->getOrgan();

        if (null === $organ) {
            if ($isBoard) {
                return;
            }

            // NotNull already reported it; saying it twice helps nobody.
            return;
        }

        $allowed = [];
        foreach ($selectableOrgans as $selectable) {
            $allowed[] = intval($selectable->getId());
        }

        if (
            in_array(
                intval($organ->getId()),
                $allowed,
                true,
            )
        ) {
            return;
        }

        $form->get('organ')->addError(new FormError(
            $this->translator->trans(
                'You cannot propose an activity for this body.',
                [],
                'validators',
            ),
        ));
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function guardAllowance(
        FormInterface $form,
        ActivityProposal $proposal,
        OptionPeriod $period,
        bool $isBoard,
    ): void {
        $organ = $proposal->getOrgan();

        // An activity the board hosts itself is held to nothing.
        if (null === $organ) {
            return;
        }

        $allowance = $this->limitResolver->allowanceFor(
            $organ,
            $period,
            $proposal,
        );

        if (!$allowance->isExhausted()) {
            return;
        }

        $form->get('organ')->addError(new FormError(
            $this->translator->trans(
                'This body has already put forward everything it may in this round.',
                [],
                'validators',
            ),
        ));

        if (!$isBoard) {
            return;
        }

        // The board can see past its own limits, so tell it what it is overriding rather than only that it cannot.
        $form->get('organ')->addError(new FormError($this->translator->trans(
            'The limit is %maximum% and %used% have been used.',
            [
                '%maximum%' => $allowance->maximum,
                '%used%' => $allowance->used,
            ],
            'validators',
        )));
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function guardDays(
        FormInterface $form,
        OptionPeriod $period,
    ): void {
        $today = new DateTime('today');

        foreach ($form->get('dateOptions') as $row) {
            $begins = $row->get('beginsAt')->getData();
            $ends = $row->get('endsAt')->getData();

            // A row missing a day was already reported by NotBlank; saying so twice helps nobody, and the entity's
            // property was never written.
            if (
                !$begins instanceof DateTime
                || !$ends instanceof DateTime
            ) {
                continue;
            }

            if ($ends < $begins) {
                $row->get('endsAt')->addError(new FormError($this->translator->trans(
                    'A day cannot end before it starts.',
                    [],
                    'validators',
                )));

                continue;
            }

            if ($begins < $today) {
                $row->get('beginsAt')->addError(new FormError($this->translator->trans(
                    'That day has already been.',
                    [],
                    'validators',
                )));

                continue;
            }

            if (
                $period->covers(
                    $begins,
                    $ends,
                )
            ) {
                continue;
            }

            $row->get('beginsAt')->addError(new FormError($this->translator->trans(
                'This round only covers %from% to %until%.',
                [
                    '%from%' => $period->getStartsAt()->format('d-m-Y'),
                    '%until%' => $period->getEndsAt()->format('d-m-Y'),
                ],
                'validators',
            )));
        }
    }

    /**
     * The bodies the person may put an activity forward for: every active body for the board, otherwise the bodies
     * they are currently installed in, mirroring {@see \App\Security\Activity\ActivityProposalVoter}.
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
            $organs[intval($organ->getId())] = $organ;
        }

        return array_values($organs);
    }

    /**
     * @return OptionPeriod[]
     */
    private function selectablePeriods(bool $isBoard): array
    {
        if ($isBoard) {
            return $this->optionPeriodRepository->findCurrentAndUpcoming(new DateTime());
        }

        return $this->optionPeriodRepository->findOpenAt(new DateTime());
    }
}
