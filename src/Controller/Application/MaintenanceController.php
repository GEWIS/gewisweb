<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\MaintenanceWindow;
use App\Entity\User\Enums\UserRoles;
use App\Form\Application\MaintenanceType;
use App\Repository\Application\MaintenanceWindowRepository;
use App\Security\User\SudoVoter;
use App\Service\Application\RealtimeNotifier;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[IsGranted(
    attribute: UserRoles::Admin->value,
    message: 'You are not allowed to manage maintenance mode.',
)]
#[IsGranted(SudoVoter::ATTRIBUTE)]
class MaintenanceController extends AbstractController
{
    public function __construct(
        private readonly MaintenanceWindowRepository $maintenanceWindowRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RealtimeNotifier $realtimeNotifier,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        path: '/admin/maintenance',
        name: 'admin/maintenance',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render(
            'frontpage/admin/maintenance.html.twig',
            [
                'windows' => $this->maintenanceWindowRepository->findAllOrdered(),
                'now' => new DateTimeImmutable(),
            ],
        );
    }

    #[Route(
        path: '/admin/maintenance/create',
        name: 'admin/maintenance/create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(Request $request): Response
    {
        $window = new MaintenanceWindow();
        $form = $this->createForm(
            MaintenanceType::class,
            $window,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'frontpage/admin/maintenance-window.html.twig',
                ['form' => $form],
            );
        }

        $this->entityManager->persist($window);
        $this->entityManager->flush();

        // Push everyone off their current page so non-admins land on the maintenance page (or a read-only site) at once
        // rather than on their next request.
        if ($window->isActiveAt(new DateTimeImmutable())) {
            $this->broadcastReload();
        }

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Maintenance window scheduled.'),
        );

        return $this->redirectToRoute('admin/maintenance');
    }

    #[IsCsrfTokenValid(
        id: 'maintenance_window_delete',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/admin/maintenance/{id}/delete',
        name: 'admin/maintenance/delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function delete(int $id): Response
    {
        $window = $this->maintenanceWindowRepository->find($id);
        if (null !== $window) {
            $wasActive = $window->isActiveAt(new DateTimeImmutable());
            $this->entityManager->remove($window);
            $this->entityManager->flush();

            if ($wasActive) {
                $this->broadcastReload();
            }

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('Maintenance window removed.'),
            );
        }

        return $this->redirectToRoute('admin/maintenance');
    }

    /**
     * Tells connected clients to reload. The window is already saved, so a hub outage must not fail the request: it is
     * logged and non-admins land in the right place on their next request anyway.
     */
    private function broadcastReload(): void
    {
        try {
            $this->realtimeNotifier->reloadPublic();
        } catch (Throwable $e) {
            $this->logger->warning(
                'Failed to broadcast a maintenance reload.',
                ['exception' => $e],
            );
        }
    }
}
