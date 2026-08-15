<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\ActivityProposal;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Decision\Organ;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\Activity\ActivityProposalType;
use App\Repository\Activity\ActivityProposalRepository;
use App\Repository\Activity\OptionPeriodRepository;
use App\Repository\Decision\OrganRepository;
use App\Security\Activity\ActivityProposalVoter;
use App\Service\Activity\ActivityProposalManager;
use App\Service\Activity\ProposalAllowanceExhausted;
use App\Service\Activity\ProposalLimitResolver;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_values;
use function intval;

/**
 * The option calendar as a body sees it: what is already claimed, what its own body has going, and the form to claim
 * a day.
 *
 * Everything lives under `/admin` because that is where the three menus already point and because proposing is
 * something an active member does, exactly like creating an activity. The board-only screens (rounds, exceptions,
 * decisions) say so on the action rather than on the class.
 */
#[IsGranted(new Expression(
    'is_granted("' . UserRoles::ActiveMember->value . '") or is_granted("' . UserRoles::Board->value . '")',
))]
#[Route(
    path: '/admin/activities/calendar',
    name: 'admin/activities/calendar/',
)]
class AdminActivityCalendarController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityProposalRepository $activityProposalRepository,
        private readonly OptionPeriodRepository $optionPeriodRepository,
        private readonly OrganRepository $organRepository,
        private readonly ActivityProposalManager $proposalManager,
        private readonly ProposalLimitResolver $limitResolver,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly WorkflowInterface $activityProposalStateMachine,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
        methods: ['GET'],
    )]
    public function index(#[CurrentUser]
        User $user,): Response
    {
        $openPeriods = $this->optionPeriodRepository->findOpenAt(new DateTime());
        $organs = $this->actableOrgans($user);

        return $this->render(
            'activity/admin/calendar/index.html.twig',
            [
                'openPeriods' => $openPeriods,
                'allowances' => [] === $openPeriods
                    ? []
                    : $this->limitResolver->allowancesFor(
                        $organs,
                        $openPeriods[0],
                    ),
                'organs' => $organs,
                'mine' => [] === $openPeriods
                    ? []
                    : $this->activityProposalRepository->findForOrgansInPeriod(
                        $openPeriods[0],
                        $organs,
                    ),
            ],
        );
    }

    #[Route(
        path: '/propose',
        name: 'propose',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function propose(
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        $proposal = new ActivityProposal();
        $proposal->setCreatedBy($user->getMember());
        $proposal->addDateOption(new ActivityDateOption());

        $form = $this->createForm(
            ActivityProposalType::class,
            $proposal,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'activity/admin/calendar/propose.html.twig',
                [
                    'form' => $form,
                    'proposal' => null,
                ],
            );
        }

        try {
            $this->proposalManager->create($proposal);
        } catch (ProposalAllowanceExhausted $exhausted) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $exhausted->getMessage(),
            );

            return $this->redirectToRoute('admin/activities/calendar/propose');
        }

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Your proposal is in. The board decides which day you get.'),
        );

        return $this->redirectToRoute(
            'admin/activities/calendar/proposal',
            ['proposal' => $proposal->getId()],
        );
    }

    #[Route(
        path: '/proposals/{proposal}',
        name: 'proposal',
        requirements: ['proposal' => '\d+'],
        methods: ['GET'],
    )]
    public function proposal(ActivityProposal $proposal): Response
    {
        $this->denyAccessUnlessGranted(
            ActivityProposalVoter::VIEW,
            $proposal,
        );

        return $this->render(
            'activity/admin/calendar/proposal.html.twig',
            [
                'proposal' => $proposal,
                'canWithdraw' => $this->activityProposalStateMachine->can(
                    $proposal,
                    'withdraw',
                ),
                'allowance' => null === $proposal->getOrgan()
                    ? null
                    : $this->limitResolver->allowanceFor(
                        $proposal->getOrgan(),
                        $proposal->getPeriod(),
                    ),
            ],
        );
    }

    #[Route(
        path: '/proposals/{proposal}/edit',
        name: 'proposal_edit',
        requirements: ['proposal' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function editProposal(
        Request $request,
        ActivityProposal $proposal,
    ): Response {
        $this->denyAccessUnlessGranted(
            ActivityProposalVoter::EDIT,
            $proposal,
        );

        $form = $this->createForm(
            ActivityProposalType::class,
            $proposal,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'activity/admin/calendar/propose.html.twig',
                [
                    'form' => $form,
                    'proposal' => $proposal,
                ],
            );
        }

        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Your proposal has been updated.'),
        );

        return $this->redirectToRoute(
            'admin/activities/calendar/proposal',
            ['proposal' => $proposal->getId()],
        );
    }

    /**
     * Taking a proposal back, which releases every day it was standing on.
     */
    #[Route(
        path: '/proposals/{proposal}/withdraw',
        name: 'proposal_withdraw',
        requirements: ['proposal' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"activity_proposal_withdraw-" ~ args["proposal"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function withdrawProposal(ActivityProposal $proposal): Response
    {
        if (
            !$this->activityProposalStateMachine->can(
                $proposal,
                'withdraw',
            )
        ) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('This proposal can no longer be taken back.'),
            );

            return $this->redirectToRoute(
                'admin/activities/calendar/proposal',
                ['proposal' => $proposal->getId()],
            );
        }

        $this->activityProposalStateMachine->apply(
            $proposal,
            'withdraw',
        );
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The proposal has been taken back and its days are free again.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/index');
    }

    /**
     * The bodies this person may put an activity forward for, which is the same rule the form and the voter use.
     *
     * @return Organ[]
     */
    private function actableOrgans(User $user): array
    {
        if ($this->security->isGranted(UserRoles::Board->value)) {
            return $this->organRepository->findActive();
        }

        $organs = [];
        foreach ($user->getMember()->getCurrentOrganInstallations() as $installation) {
            $organ = $installation->getOrgan();
            $organs[intval($organ->getId())] = $organ;
        }

        return array_values($organs);
    }
}
