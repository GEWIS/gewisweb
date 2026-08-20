<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\OptionPeriod;
use App\Entity\Activity\PeriodProposalLimit;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\Enums\UserRoles;
use App\Form\Activity\OptionPeriodType;
use App\Form\Activity\PeriodProposalLimitType;
use App\Repository\Activity\ActivityProposalRepository;
use App\Repository\Activity\OptionPeriodRepository;
use App\Repository\Activity\PeriodProposalLimitRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function count;

/**
 * The board's rounds of the option calendar: when bodies may propose, which days they may propose, and the exceptions
 * that apply to one body in one round.
 *
 * Opening a round writes nothing per body. Every body is answered by {@see \App\Service\Activity\ProposalLimitResolver}
 * when it asks, so this screen only ever collects exceptions.
 */
#[IsGranted(UserRoles::Board->value)]
#[Route(
    path: '/admin/activities/calendar/periods',
    name: 'admin/activities/calendar/periods/',
)]
class AdminOptionPeriodController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OptionPeriodRepository $optionPeriodRepository,
        private readonly PeriodProposalLimitRepository $periodProposalLimitRepository,
        private readonly ActivityProposalRepository $activityProposalRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render(
            'activity/admin/calendar/periods/index.html.twig',
            [
                'periods' => $this->optionPeriodRepository->findAllNewestFirst(),
                'now' => new DateTime(),
            ],
        );
    }

    #[Route(
        path: '/create',
        name: 'create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(Request $request): Response
    {
        $period = new OptionPeriod();
        $form = $this->createForm(
            OptionPeriodType::class,
            $period,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'activity/admin/calendar/periods/form.html.twig',
                [
                    'form' => $form,
                    'period' => null,
                ],
            );
        }

        $this->entityManager->persist($period);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The round is open. Every body may propose without anything being set up for it.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/periods/index');
    }

    #[Route(
        path: '/{period}/edit',
        name: 'edit',
        requirements: ['period' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        OptionPeriod $period,
    ): Response {
        $form = $this->createForm(
            OptionPeriodType::class,
            $period,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'activity/admin/calendar/periods/form.html.twig',
                [
                    'form' => $form,
                    'period' => $period,
                ],
            );
        }

        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The round has been updated.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/periods/index');
    }

    /**
     * A round with proposals in it is not deleted: the dates bodies are holding would go with it. It can be closed to
     * new proposals by moving the window instead.
     */
    #[Route(
        path: '/{period}/delete',
        name: 'delete',
        requirements: ['period' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"option_period_delete-" ~ args["period"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function delete(OptionPeriod $period): Response
    {
        if (!$period->getProposals()->isEmpty()) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans(
                    'This round cannot be removed: bodies have proposed in it. Move its window instead to stop it '
                    . 'taking anything new.',
                ),
            );

            return $this->redirectToRoute('admin/activities/calendar/periods/index');
        }

        foreach ($this->periodProposalLimitRepository->findForPeriod($period) as $limit) {
            $this->entityManager->remove($limit);
        }

        $this->entityManager->remove($period);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The round has been removed.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/periods/index');
    }

    /**
     * The exceptions that apply in this round alone. A body without a row here falls back to its standing exception,
     * then to the round's own number, then to the number every body gets.
     */
    #[Route(
        path: '/{period}/limits',
        name: 'limits',
        requirements: ['period' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function limits(
        Request $request,
        OptionPeriod $period,
    ): Response {
        $limit = new PeriodProposalLimit();
        $limit->setPeriod($period);

        $form = $this->createForm(
            PeriodProposalLimitType::class,
            $limit,
        )->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->entityManager->persist($limit);
            $this->entityManager->flush();

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('The exception has been recorded.'),
            );

            return $this->redirectToRoute(
                'admin/activities/calendar/periods/limits',
                ['period' => $period->getId()],
            );
        }

        return $this->render(
            'activity/admin/calendar/periods/limits.html.twig',
            [
                'form' => $form,
                'period' => $period,
                'limits' => $this->periodProposalLimitRepository->findForPeriod($period),
                'proposalCount' => count($this->activityProposalRepository->findAwaitingDecision($period)),
            ],
        );
    }

    #[Route(
        path: '/{period}/limits/{limit}/delete',
        name: 'limit_delete',
        requirements: [
            'period' => '\d+',
            'limit' => '\d+',
        ],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"period_proposal_limit_delete-" ~ args["limit"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function deleteLimit(
        OptionPeriod $period,
        PeriodProposalLimit $limit,
    ): Response {
        $this->entityManager->remove($limit);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The exception has been removed; the body is back on the usual number.'),
        );

        return $this->redirectToRoute(
            'admin/activities/calendar/periods/limits',
            ['period' => $period->getId()],
        );
    }
}
