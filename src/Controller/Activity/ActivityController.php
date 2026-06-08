<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Application\Enums\Languages;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Activity\ActivityRepository;
use App\Repository\Activity\ExternalSignupVerificationRepository;
use App\ViewModel\Activity\SignupListView;
use Locale;
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
        private readonly ExternalSignupVerificationRepository $verificationRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('activity/index.html.twig');
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
        defaults: ['year' => null],
    )]
    #[IsGranted(UserRoles::User->value)]
    public function myArchive(
        #[CurrentUser]
        User $user,
        ?int $year = null,
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
                $this->verificationRepository->findPendingExternalSignupIdsForList($signupList),
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
