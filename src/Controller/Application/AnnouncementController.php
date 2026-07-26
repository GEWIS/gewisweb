<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Application\Announcement;
use App\Entity\Application\ApplicationLocalisedText;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\Enums\UserRoles;
use App\Form\Application\AnnouncementType;
use App\Repository\Application\AnnouncementRepository;
use App\Security\User\SudoVoter;
use App\Service\Application\RealtimeNotifier;
use App\Service\Application\RealtimePayload;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(
    attribute: UserRoles::Admin->value,
    message: 'You are not allowed to send announcements.',
)]
#[IsGranted(SudoVoter::ATTRIBUTE)]
class AnnouncementController extends AbstractController
{
    public function __construct(
        private readonly RealtimeNotifier $realtimeNotifier,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly AnnouncementRepository $announcementRepository,
    ) {
    }

    #[Route(
        path: '/admin/announcement',
        name: 'admin/announcement',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render(
            'frontpage/admin/announcements.html.twig',
            ['announcements' => $this->announcementRepository->findAllNewestFirst()],
        );
    }

    #[Route(
        path: '/admin/announcement/create',
        name: 'admin/announcement/create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(Request $request): Response
    {
        $announcement = new Announcement();
        $announcement->setTitle(new ApplicationLocalisedText());
        $announcement->setBody(new ApplicationLocalisedText());

        $form = $this->createForm(
            AnnouncementType::class,
            $announcement,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'frontpage/admin/announcement.html.twig',
                ['form' => $form],
            );
        }

        $this->realtimeNotifier->toPublic(new RealtimePayload(
            $announcement->getLevel(),
            $announcement->getBody()->toArray(),
            title: $announcement->getTitle()->toArray(),
        ));

        $endsAt = $form->get('endsAt')->getData();
        if (
            true === $form->get('sticky')->getData()
            && $endsAt instanceof DateTimeImmutable
        ) {
            $announcement->setEndsAt($endsAt);
            $announcement->setCreatedAt(new DateTimeImmutable());
            $this->entityManager->persist($announcement);
            $this->entityManager->flush();
        }

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Announcement sent.'),
        );

        return $this->redirectToRoute('admin/announcement');
    }

    #[IsCsrfTokenValid(
        id: 'announcement_delete',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/admin/announcement/{id}/delete',
        name: 'admin/announcement/delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function delete(int $id): Response
    {
        $announcement = $this->announcementRepository->find($id);
        if (null !== $announcement) {
            $this->entityManager->remove($announcement);
            $this->entityManager->flush();

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('Announcement removed.'),
            );
        }

        return $this->redirectToRoute('admin/announcement');
    }
}
