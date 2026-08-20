<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\BudgetClearance;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Activity\ActivityProposalRepository;
use App\Repository\Activity\OptionPeriodRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function count;
use function ksort;

/**
 * Where the board decides which body gets which day.
 *
 * The queue groups everything waiting by the day it is asked for, in the order it was asked, so who was first is
 * plain to see. It is only ever shown, never enforced: the board may pick whichever it likes, which is the exception
 * power it has always had and which no screen should take away from it.
 *
 * Reserving a day starts the activity itself off as a draft and releases every other day that proposal was standing
 * on, both through the workflow rather than here.
 */
#[IsGranted(UserRoles::Board->value)]
#[Route(
    path: '/admin/activities/calendar/decisions',
    name: 'admin/activities/calendar/decisions/',
)]
class AdminProposalDecisionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityProposalRepository $activityProposalRepository,
        private readonly OptionPeriodRepository $optionPeriodRepository,
        private readonly TranslatorInterface $translator,
        private readonly WorkflowInterface $activityProposalStateMachine,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        $waiting = $this->activityProposalRepository->findAwaitingDecision();

        return $this->render(
            'activity/admin/calendar/decisions.html.twig',
            [
                'waiting' => $waiting,
                'contested' => $this->contestedDays($waiting),
                'holding' => $this->activityProposalRepository->findDueToLapse(new DateTime('+1 year')),
                'periods' => $this->optionPeriodRepository->findCurrentAndUpcoming(new DateTime()),
            ],
        );
    }

    #[Route(
        path: '/options/{option}/approve',
        name: 'option_approve',
        requirements: ['option' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_date_option_approve-" ~ args["option"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function approveOption(
        ActivityDateOption $option,
        #[CurrentUser]
        User $user,
    ): Response {
        $proposal = $option->getProposal();

        if (
            !$this->activityProposalStateMachine->can(
                $proposal,
                'schedule',
            )
        ) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('This proposal is no longer waiting for a day.'),
            );

            return $this->redirectToRoute('admin/activities/calendar/decisions/index');
        }

        $option->setDecidedBy($user->getMember());
        $option->setDecidedAt(new DateTime());

        $proposal->setChosenOption($option);
        $proposal->setDecidedBy($user->getMember());
        $proposal->setDecidedAt(new DateTime());

        $this->activityProposalStateMachine->apply(
            $proposal,
            'schedule',
        );
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The day is reserved and the activity has been started for them.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/decisions/index');
    }

    #[Route(
        path: '/{proposal}/decline',
        name: 'decline',
        requirements: ['proposal' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_proposal_decline-" ~ args["proposal"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function decline(
        ActivityProposal $proposal,
        #[CurrentUser]
        User $user,
    ): Response {
        if (
            !$this->activityProposalStateMachine->can(
                $proposal,
                'decline',
            )
        ) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('This proposal is no longer waiting for a decision.'),
            );

            return $this->redirectToRoute('admin/activities/calendar/decisions/index');
        }

        $proposal->setDecidedBy($user->getMember());
        $proposal->setDecidedAt(new DateTime());

        $this->activityProposalStateMachine->apply(
            $proposal,
            'decline',
        );
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The proposal has been turned down and its days are free again.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/decisions/index');
    }

    /**
     * Records that the financial side is settled, either because a budget was approved or because there is nothing to
     * approve. Posting it again with no outcome takes it back, which is the way out of recording the wrong one.
     */
    #[Route(
        path: '/{proposal}/clearance',
        name: 'clearance',
        requirements: ['proposal' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_proposal_clearance-" ~ args["proposal"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function clearance(
        Request $request,
        ActivityProposal $proposal,
        #[CurrentUser]
        User $user,
    ): Response {
        $outcome = BudgetClearance::tryFrom($request->request->getString('clearance'));

        if (null === $outcome) {
            return $this->revokeClearance($proposal);
        }

        if (
            !$this->activityProposalStateMachine->can(
                $proposal,
                'clear_budget',
            )
        ) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('There is no reserved day to settle for this proposal.'),
            );

            return $this->redirectToRoute('admin/activities/calendar/decisions/index');
        }

        $this->activityProposalStateMachine->apply(
            $proposal,
            'clear_budget',
        );

        // After the transition, so the listener that clears the stamp on the way out of `scheduled` cannot undo it.
        $proposal->setBudgetClearance($outcome);
        $proposal->setBudgetClearedBy($user->getMember());
        $proposal->setBudgetClearedAt(new DateTime());
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Recorded. This day will not be chased or released any more.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/decisions/index');
    }

    private function revokeClearance(ActivityProposal $proposal): Response
    {
        if (
            !$this->activityProposalStateMachine->can(
                $proposal,
                'revoke_clearance',
            )
        ) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('There is nothing recorded to take back.'),
            );

            return $this->redirectToRoute('admin/activities/calendar/decisions/index');
        }

        $this->activityProposalStateMachine->apply(
            $proposal,
            'revoke_clearance',
        );
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Taken back. The day can be chased and released again.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/decisions/index');
    }

    /**
     * The days more than one body is asking for, keyed by the day, each list in the order they asked.
     *
     * @param ActivityProposal[] $waiting
     *
     * @return array<string, ActivityDateOption[]>
     */
    private function contestedDays(array $waiting): array
    {
        $byDay = [];

        foreach ($waiting as $proposal) {
            foreach ($proposal->getDateOptions() as $dateOption) {
                if (!$dateOption->getStatus()->isStanding()) {
                    continue;
                }

                $byDay[$dateOption->getBeginsAt()->format('Y-m-d')][] = $dateOption;
            }
        }

        // A day only one body wants is not a decision worth grouping; it is decided from the proposal itself.
        foreach ($byDay as $day => $options) {
            if (1 < count($options)) {
                continue;
            }

            unset($byDay[$day]);
        }

        ksort($byDay);

        return $byDay;
    }
}
