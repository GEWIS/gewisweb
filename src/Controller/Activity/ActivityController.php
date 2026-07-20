<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Application\Enums\Languages;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Activity\ActivityRepository;
use App\ViewModel\Activity\SignupListView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
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
    public function index(): Response
    {
        return $this->render(
            'activity/index.html.twig',
            ['years' => $this->activityRepository->getApprovedActivityYears()],
        );
    }

    #[Route(
        path: '/my',
        name: 'my',
    )]
    #[IsGranted(UserRoles::User->value)]
    public function my(
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->render(
            'activity/my.html.twig',
            ['years' => $this->activityRepository->getSubscribedAssociationYears($user->getMember())],
        );
    }

    #[Route(
        path: '/search',
        name: 'search',
    )]
    public function search(): Response
    {
        return $this->render('activity/search.html.twig');
    }

    #[Route(
        path: '/archive/{year}',
        name: 'archive',
        requirements: ['year' => '\d{4}'],
    )]
    public function archive(int $year): Response
    {
        return $this->render(
            'activity/archive.html.twig',
            [
                'year' => $year,
                'years' => $this->activityRepository->getApprovedActivityYears(),
            ],
        );
    }

    #[Route(
        path: '/archive/my/{year}',
        name: 'archive/my',
        requirements: ['year' => '\d{4}'],
    )]
    #[IsGranted(UserRoles::User->value)]
    public function myArchive(
        #[CurrentUser]
        User $user,
        int $year,
    ): Response {
        return $this->render(
            'activity/archive-my.html.twig',
            [
                'year' => $year,
                'years' => $this->activityRepository->getSubscribedAssociationYears($user->getMember()),
            ],
        );
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
            || null === $entity->getLiveRevision()
            // An unpublished activity is taken out of public view entirely: its direct URL 404s like a never-approved
            // one. (A cancelled activity, by contrast, stays visible with a notice.)
            || $entity->isUnpublished()
        ) {
            throw $this->createNotFoundException();
        }

        return $this->renderActivityView($entity);
    }

    /**
     * Render the public activity page. The per-list sign-up UI is a live component (member) or a server-rendered guest
     * panel, so this only needs the read-model views and the calendar event.
     */
    private function renderActivityView(Activity $entity): Response
    {
        $canViewDetails = $this->isGranted(UserRoles::User->value);
        $user = $this->getUser();
        $viewerLidnr = $user instanceof User
            ? $user->getMember()->getLidnr()
            : null;

        $signupListViews = [];
        foreach ($entity->getLiveSignupLists() as $signupList) {
            $signupListViews[] = SignupListView::fromSignupList(
                $signupList,
                $canViewDetails,
                $viewerLidnr,
                $this->translator,
            );
        }

        $language = Languages::current();

        // A cancelled activity carries a [CANCELLED] marker everywhere its title is shown, the calendar entry included.
        $title = $entity->getName()->getText($language) ?? '';
        if ($entity->isCancelled()) {
            $title = $this->translator->trans('[CANCELLED]') . ' ' . $title;
        }

        $calendarEvent = new CalendarEvent(
            title: $title,
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
