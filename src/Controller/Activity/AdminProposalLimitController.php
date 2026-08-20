<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\ProposalLimit;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\Enums\UserRoles;
use App\Form\Activity\ProposalLimitType;
use App\Repository\Activity\ProposalLimitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Standing exceptions to how many activities a body may propose per round.
 *
 * A short list of exceptions, not a roll call. Bodies that are on the ordinary number never appear, so the board never
 * has to fill in a number for a body it has no opinion about, and a body founded tomorrow works without anybody
 * touching this screen. Exceptions set here hold for every round until the board changes them, which is what the
 * bodies that need one (a first-year committee, say) actually want; a single round can still be treated differently
 * from the round's own screen.
 */
#[IsGranted(UserRoles::Board->value)]
#[Route(
    path: '/admin/activities/calendar/limits',
    name: 'admin/activities/calendar/limits/',
)]
class AdminProposalLimitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProposalLimitRepository $proposalLimitRepository,
        private readonly TranslatorInterface $translator,
        private readonly int $defaultMaxProposals,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function index(Request $request): Response
    {
        $limit = new ProposalLimit();
        $form = $this->createForm(
            ProposalLimitType::class,
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
                $this->translator->trans('The exception has been recorded and holds until you change it.'),
            );

            return $this->redirectToRoute('admin/activities/calendar/limits/index');
        }

        return $this->render(
            'activity/admin/calendar/limits/index.html.twig',
            [
                'form' => $form,
                'limits' => $this->proposalLimitRepository->findAllWithOrgan(),
                'defaultMaxProposals' => $this->defaultMaxProposals,
            ],
        );
    }

    #[Route(
        path: '/{limit}/edit',
        name: 'edit',
        requirements: ['limit' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        ProposalLimit $limit,
    ): Response {
        $form = $this->createForm(
            ProposalLimitType::class,
            $limit,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'activity/admin/calendar/limits/edit.html.twig',
                [
                    'form' => $form,
                    'limit' => $limit,
                ],
            );
        }

        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The exception has been updated.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/limits/index');
    }

    #[Route(
        path: '/{limit}/delete',
        name: 'delete',
        requirements: ['limit' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"proposal_limit_delete-" ~ args["limit"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function delete(ProposalLimit $limit): Response
    {
        $this->entityManager->remove($limit);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The exception has been removed; the body is back on the usual number.'),
        );

        return $this->redirectToRoute('admin/activities/calendar/limits/index');
    }
}
