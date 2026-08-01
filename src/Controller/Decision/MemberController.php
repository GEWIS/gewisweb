<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\MeetingRepository;
use App\Repository\Decision\MemberRepository;
use App\Repository\Photo\ProfilePhotoRepository;
use App\Service\Application\FileDownloadHelper;
use App\Service\Application\FileStorage;
use App\Service\Decision\MemberInfoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function array_map;
use function assert;
use function basename;
use function mb_strlen;
use function trim;

#[IsGranted(
    attribute: UserRoles::User->value,
    message: 'You are not allowed to view members.',
)]
#[Route(
    path: '/members',
    name: 'members/',
)]
class MemberController extends AbstractController
{
    /**
     * @param array<string, string> $regulations
     * @param array<string, string> $membersAreaLinks
     */
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly MeetingRepository $meetingRepository,
        private readonly MemberInfoService $memberInfoService,
        private readonly ProfilePhotoRepository $profilePhotoRepository,
        private readonly FileStorage $fileStorage,
        private readonly FileDownloadHelper $fileDownloadHelper,
        #[Autowire('%app.regulations%')]
        private readonly array $regulations,
        #[Autowire('%app.members_area_links%')]
        private readonly array $membersAreaLinks,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $member = $user->getMember();

        $recentMeetings = [];
        foreach (
            [
                MeetingTypes::ALV,
                MeetingTypes::BV,
                MeetingTypes::VV,
            ] as $type
        ) {
            $recentMeetings[$type->value] = $this->meetingRepository->findPast(
                3,
                $type,
            );
        }

        return $this->render(
            'decision/index.html.twig',
            [
                'member' => $member,
                'recentMeetings' => $recentMeetings,
                'upcomingMeetings' => $this->meetingRepository->findUpcomingAnnouncedMeetings(),
                'links' => $this->membersAreaLinks,
            ],
        );
    }

    /**
     * The member directory. Graduates keep access to the meeting pages but not to the member register, mirroring the
     * old access rules.
     */
    #[IsGranted(
        attribute: UserRoles::Member->value,
        message: 'You are not allowed to search for members.',
    )]
    #[Route(
        path: '/find',
        name: 'find',
        methods: ['GET'],
    )]
    public function find(): Response
    {
        return $this->render('decision/members/find.html.twig');
    }

    /**
     * Name autocomplete for the member directory; separate from the photo-tag endpoint, which excludes members who
     * opted out of tagging.
     */
    #[IsGranted(
        attribute: UserRoles::Member->value,
        message: 'You are not allowed to search for members.',
    )]
    #[Route(
        path: '/find/results',
        name: 'find/results',
        methods: ['GET'],
    )]
    public function findResults(#[MapQueryParameter]
    string $q = '',): JsonResponse
    {
        $query = trim($q);
        if (mb_strlen($query) < 2) {
            return new JsonResponse([]);
        }

        return new JsonResponse(array_map(
            fn (array $row): array => $row + [
                'url' => $this->generateUrl(
                    'members/view',
                    ['member' => $row['lidnr']],
                ),
            ],
            $this->memberRepository->searchDirectory($query),
        ));
    }

    /**
     * Serves a regulation from the SFTP-mirrored public archive by its dashboard slug.
     */
    #[Route(
        path: '/regulations/{regulation}',
        name: 'regulations',
        requirements: ['regulation' => '[a-z0-9-]+'],
        methods: ['GET'],
    )]
    public function downloadRegulation(string $regulation): Response
    {
        $archivePath = $this->regulations[$regulation] ?? null;

        if (null === $archivePath) {
            throw $this->createNotFoundException();
        }

        $storedPath = 'public-archive/' . $archivePath . '.pdf';

        if (!$this->fileStorage->exists($storedPath)) {
            throw $this->createNotFoundException();
        }

        return $this->fileDownloadHelper->download(
            $storedPath,
            basename($archivePath) . '.pdf',
            'application/pdf',
        );
    }

    /**
     * Name autocomplete for the photo tag UI: matches current members by name and returns their lidnr and full name.
     */
    #[Route(
        path: '/search',
        name: 'search',
        methods: ['GET'],
    )]
    public function search(#[MapQueryParameter]
    string $q = '',): JsonResponse
    {
        $query = trim($q);
        if (mb_strlen($query) < 2) {
            return new JsonResponse([]);
        }

        return new JsonResponse(array_map(
            static fn (array $row): array => [
                'lidnr' => $row['lidnr'],
                'fullName' => $row['fullName'],
            ],
            $this->memberRepository->searchByName($query),
        ));
    }

    #[Route(
        path: '/me',
        name: 'me',
    )]
    #[Route(
        path: '/{member}',
        name: 'view',
        requirements: ['member' => '[1-9][0-9]{,4}'],
        defaults: ['member' => null],
    )]
    public function member(
        Request $request,
        ?Member $member = null,
    ): Response {
        if (
            null !== $request->attributes->get('member')
            && (
                null === $member
                || true === $member->getDeleted()
            )
        ) {
            throw $this->createNotFoundException();
        }

        if (null === $member) {
            $user = $this->getUser();
            assert($user instanceof User);

            $member = $user->getMember();
        } else {
            if (
                $member->isExpired()
                && $this->isGranted(UserRoles::Admin->value)
            ) {
                throw $this->createNotFoundException();
            }
        }

        $profilePhoto = $this->profilePhotoRepository->getProfilePhotoByLidnr($member->getLidnr());

        return $this->render(
            'decision/member.html.twig',
            [
                'member' => $member,
                'committees' => $this->memberInfoService->getOrganMemberships($member),
                'profilePhoto' => $profilePhoto?->getPhoto(),
            ],
        );
    }
}
