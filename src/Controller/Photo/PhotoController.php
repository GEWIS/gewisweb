<?php

declare(strict_types=1);

namespace App\Controller\Photo;

use App\Entity\Photo\Album;
use App\Entity\Photo\Enums\AlbumType;
use App\Entity\Photo\MemberAlbum;
use App\Entity\Photo\OrganAlbum;
use App\Entity\Photo\Photo;
use App\Entity\Photo\WeeklyPhoto;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\MemberRepository;
use App\Repository\Decision\OrganRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\PhotoRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Security\Photo\PhotoVoter;
use App\Service\Application\FileDownloadHelper;
use App\Service\Photo\AlbumService;
use App\Service\Photo\PhotoPrivacyService;
use App\Service\Photo\PhotoService;
use App\Service\Photo\WeeklyPhotoService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

use function array_map;
use function in_array;
use function intval;
use function max;
use function min;
use function pathinfo;
use function sprintf;

use const PATHINFO_EXTENSION;

#[IsGranted(
    attribute: UserRoles::User->value,
    message: 'You are not allowed to view photos.',
)]
#[Route(
    path: '/photos',
    name: 'photo/',
)]
class PhotoController extends AbstractController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly PhotoService $photoService,
        private readonly PhotoRepository $photoRepository,
        private readonly WeeklyPhotoRepository $weeklyPhotoRepository,
        private readonly WeeklyPhotoService $weeklyPhotoService,
        private readonly MemberRepository $memberRepository,
        private readonly MemberTagRepository $memberTagRepository,
        private readonly OrganRepository $organRepository,
        private readonly FileDownloadHelper $fileDownloadHelper,
        private readonly SluggerInterface $slugger,
        private readonly PhotoPrivacyService $photoPrivacyService,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(
        #[MapQueryParameter]
        ?int $year = null,
    ): Response {
        // The year-switcher navigates here with ?year=; resolve it against the years that actually have albums and
        // otherwise default to the most recent one, so the page never lands on an empty year.
        $years = $this->albumService->getViewableRootAlbumYears();
        $selectedYear = null !== $year && in_array(
            $year,
            $years,
            true,
        )
            ? $year
            : ($years[0] ?? null);

        return $this->render(
            'photo/index.html.twig',
            [
                'year' => $selectedYear,
                'years' => $years,
            ],
        );
    }

    #[Route(
        path: '/search',
        name: 'search',
    )]
    public function search(): Response
    {
        return $this->render('photo/search.html.twig');
    }

    #[Route(
        path: '/weekly',
        name: 'weekly',
    )]
    public function weekly(): Response
    {
        return $this->render(
            'photo/weekly.html.twig',
            [
                'current' => $this->weeklyPhotoRepository->getCurrentPhotoOfTheWeek(),
                'photosByYear' => $this->weeklyPhotoService->getPhotosByYear(),
            ],
        );
    }

    /**
     * The whole album as the PhotoSwipe data source (signed variant URLs), fetched by the viewer so a `#pid` deep link
     * works even when the grid has only rendered its first page.
     */
    #[Route(
        path: '/album/{album}/manifest',
        name: 'manifest',
        requirements: ['album' => '\d+'],
    )]
    public function manifest(int $album): JsonResponse
    {
        return new JsonResponse(
            $this->photoService->getAlbumManifest($this->viewableAlbum($album)),
        );
    }

    /**
     * The viewer manifest for one association year's virtual weekly album (its photos of the week).
     */
    #[Route(
        path: '/weekly/{year}/manifest',
        name: 'weekly_manifest',
        requirements: ['year' => '\d+'],
    )]
    public function weeklyManifest(int $year): JsonResponse
    {
        return new JsonResponse(
            $this->photoService->getWeeklyManifest($this->weeklyPhotoService->getPhotosInYear($year)),
        );
    }

    /**
     * The viewer manifest for a member's virtual album, filtered to what the current viewer may see and flagging the
     * member's own hidden photos. Loaded by the gallery on that member's photo page.
     */
    #[Route(
        path: '/member/{member}/manifest',
        name: 'member_manifest',
        requirements: ['member' => '\d+'],
    )]
    public function memberManifest(int $member): JsonResponse
    {
        $memberEntity = $this->memberRepository->find($member)
            ?? throw new NotFoundHttpException();
        $tagged = $this->photoRepository->getAlbumPhotos(new MemberAlbum($member, $memberEntity));
        $filtered = $this->photoPrivacyService->filterTaggedPhotos(
            $memberEntity,
            $tagged,
        );

        return new JsonResponse(
            $this->photoService->getMemberManifest(
                $filtered['visible'],
                $filtered['hidden'],
            ),
        );
    }

    /**
     * Download a photo's original file, named after the legacy `{album}-{year}-{id}.{ext}` scheme.
     */
    #[Route(
        path: '/album/{album}/photo/{photo}/download',
        name: 'download',
        requirements: [
            'album' => '\d+',
            'photo' => '\d+',
        ],
    )]
    public function download(
        int $album,
        int $photo,
    ): Response {
        $albumEntity = $this->viewableAlbum($album);
        $photoEntity = $this->photoRepository->find($photo);

        if (
            null === $photoEntity
            || $photoEntity->getAlbum()->getId() !== $albumEntity->getId()
            || !$this->isGranted(
                PhotoVoter::DOWNLOAD,
                $photoEntity,
            )
        ) {
            throw new NotFoundHttpException();
        }

        $filename = sprintf(
            '%s-%s-%d.%s',
            $this->slugger->slug($albumEntity->getName())->lower(),
            $albumEntity->getStartDateTime()?->format('Y') ?? 'undated',
            $photoEntity->getId(),
            pathinfo(
                $photoEntity->getPath(),
                PATHINFO_EXTENSION,
            ),
        );

        return $this->fileDownloadHelper->download(
            $photoEntity->getPath(),
            $filename,
        );
    }

    #[Route(
        path: '/hidden/hide',
        name: 'hidden_hide',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'photo_hidden',
        tokenKey: '_csrf_token',
    )]
    public function hidePhotos(
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->applyHidden(
            $request,
            $user,
            false,
        );
    }

    #[Route(
        path: '/hidden/unhide',
        name: 'hidden_unhide',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'photo_hidden',
        tokenKey: '_csrf_token',
    )]
    public function unhidePhotos(
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        return $this->applyHidden(
            $request,
            $user,
            true,
        );
    }

    private function applyHidden(
        Request $request,
        User $user,
        bool $unhide,
    ): Response {
        $member = $user->getMember();
        $photos = $this->selectedPhotos($request);

        if ([] !== $photos) {
            if ($unhide) {
                $this->photoPrivacyService->unhide(
                    $member,
                    $photos,
                );
            } else {
                $this->photoPrivacyService->hide(
                    $member,
                    $photos,
                );
            }
        }

        return $this->redirectToRoute(
            'photo/album',
            [
                'type' => AlbumType::Member->value,
                'album' => $member->getLidnr(),
            ],
        );
    }

    /**
     * @return Photo[]
     */
    private function selectedPhotos(Request $request): array
    {
        return $this->photoRepository->findByIds(
            array_map(
                intval(...),
                $request->request->all('photos'),
            ),
        );
    }

    #[Route(
        path: '/{type}/{album}',
        name: 'album',
        requirements: [
            'album' => '\d+',
        ],
    )]
    public function album(
        AlbumType $type,
        int $album,
    ): Response {
        // A regular album is stored; the others are virtual sets keyed by the {album} route value: a member's tagged
        // photos (their lidnr), a year's photos of the week (the year), or an organ's tagged photos (the organ id).
        return match ($type) {
            AlbumType::Regular => $this->regularAlbum($album),
            AlbumType::Member => $this->memberAlbum($album),
            AlbumType::Weekly => $this->weeklyAlbum($album),
            AlbumType::Body => $this->bodyAlbum($album),
        };
    }

    private function regularAlbum(int $album): Response
    {
        $albumEntity = $this->viewableAlbum($album);

        return $this->render(
            'photo/album.html.twig',
            [
                'album' => $albumEntity,
                'children' => $this->albumService->getViewableChildren($albumEntity),
            ],
        );
    }

    /**
     * The photos a member is tagged in, shown through the same viewer as a real album (opened from this virtual album,
     * with a button to jump to each photo's real album). The gallery fetches its photos through the member manifest.
     */
    private function memberAlbum(int $lidnr): Response
    {
        $member = $this->memberRepository->find($lidnr)
            ?? throw new NotFoundHttpException();

        return $this->render(
            'photo/member.html.twig',
            [
                'member' => $member,
                // Lets the page tell "never tagged" apart from "tagged, but all hidden from this viewer" in its empty
                // state, without leaking which or how many photos exist.
                'memberHasTags' => $this->memberTagRepository->hasTags($lidnr),
            ],
        );
    }

    /**
     * The photos an organ is tagged in, each linking into its real album. Like a member album this is a bounded set, so
     * it renders server-side; an unknown organ 404s, one without tagged photos renders empty.
     */
    private function bodyAlbum(int $organId): Response
    {
        $organ = $this->organRepository->findOrgan($organId)
            ?? throw new NotFoundHttpException();

        return $this->render(
            'photo/body.html.twig',
            [
                'organ' => $organ,
                'photos' => $this->photoRepository->getAlbumPhotos(new OrganAlbum($organId, $organ)),
            ],
        );
    }

    /**
     * The photos of the week of one association year, each linking into its real album. A year with no photos of the
     * week does not exist, so it 404s.
     */
    private function weeklyAlbum(int $year): Response
    {
        $weeklyPhotos = $this->weeklyPhotoService->getPhotosByYear()[$year] ?? [];
        if ([] === $weeklyPhotos) {
            throw new NotFoundHttpException();
        }

        $weeks = array_map(
            static fn (WeeklyPhoto $weeklyPhoto): DateTime => $weeklyPhoto->getWeek(),
            $weeklyPhotos,
        );

        // The gallery itself fetches the year's photos through the weekly manifest (route above).
        return $this->render(
            'photo/weekly-album.html.twig',
            [
                'year' => $year,
                'startDate' => min($weeks),
                'endDate' => max($weeks),
            ],
        );
    }

    private function viewableAlbum(int $album): Album
    {
        return $this->albumService->findViewableAlbum($album)
            ?? throw new NotFoundHttpException();
    }
}
