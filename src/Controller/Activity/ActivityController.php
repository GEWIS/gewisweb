<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Application\Enums\Languages;
use App\Entity\Decision\AssociationYear;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Activity\ActivityRepository;
use App\View\Activity\SignupListView;
use Locale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\CalendarLink\CalendarEvent;

#[Route(
    path: '/activities',
    name: 'activity/',
)]
class ActivityController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    #[Route(
        path: '/{category}',
        name: 'category',
        requirements: ['category' => '[a-z][0-9a-z\_\-]{2,31}'],
        defaults: ['category' => null],
        // Lower priority so static routes such as `/my` are not captured by this catch-all single-segment route.
        priority: -10,
    )]
    public function index(?string $category = null): Response
    {
        return $this->render(
            'activity/index.html.twig',
            ['initialCategory' => $category],
        );
    }

    #[Route(
        path: '/my',
        name: 'my',
    )]
    #[IsGranted(UserRoles::User->value)]
    public function my(): Response
    {
        return $this->render('activity/my.html.twig');
    }

    #[Route(
        path: '/archive/{year}',
        name: 'archive',
        requirements: ['year' => '\d{4}'],
        defaults: ['year' => null],
    )]
    public function archive(?int $year = null): Response
    {
        $yearStart = null;
        $yearEnd = null;

        if (null !== $year) {
            $associationYear = AssociationYear::fromYear($year);
            $yearStart = $associationYear->getStartDate()->format('Y-m-d');
            $yearEnd = $associationYear->getEndDate()->format('Y-m-d');
        }

        return $this->render(
            'activity/archive.html.twig',
            [
                'yearStart' => $yearStart,
                'yearEnd' => $yearEnd,
            ],
        );
    }

    #[Route(
        path: '/archive/my',
        name: 'archive/my',
    )]
    #[IsGranted(UserRoles::User->value)]
    public function myArchive(): Response
    {
        return $this->render('activity/archive-my.html.twig');
    }

    #[Route(
        path: '/view/{activity}',
        name: 'view',
        requirements: ['activity' => '\d+'],
    )]
    public function view(int $activity): Response
    {
        $entity = $this->activityRepository->find($activity);
        if (
            null === $entity
            || Activity::STATUS_APPROVED !== $entity->getStatus()
        ) {
            throw $this->createNotFoundException();
        }

        $canViewDetails = $this->isGranted(UserRoles::User->value);
        $user = $this->getUser();
        $viewerLidnr = $user instanceof User
            ? $user->getMember()->getLidnr()
            : null;

        $signupListViews = [];
        foreach ($entity->getSignupLists() as $signupList) {
            $signupListViews[] = SignupListView::fromSignupList(
                $signupList,
                $canViewDetails,
                $viewerLidnr,
                $this->translator,
            );
        }

        $language = 'nl' === Locale::getDefault()
            ? Languages::Dutch
            : Languages::English;

        $calendarEvent = new CalendarEvent(
            title: $entity->getName()->getText($language) ?? '',
            start: $entity->getBeginTime(),
            end: $entity->getEndTime(),
            location: $entity->getLocation()->getText($language),
            url: $this->generateUrl(
                'activity/view',
                ['activity' => $entity->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        );

        return $this->render(
            'activity/view.html.twig',
            [
                'activity' => $entity,
                'signupListViews' => $signupListViews,
                'calendarEvent' => $calendarEvent,
            ],
        );
    }
}
