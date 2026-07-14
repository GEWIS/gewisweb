<?php

declare(strict_types=1);

namespace App\Controller\Photo;

use App\Entity\Photo\Album;
use App\Entity\Photo\Enums\AlbumType;
use App\Entity\Photo\MemberAlbum;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Decision\MemberRepository;
use App\Repository\Photo\PhotoRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Security\Photo\PhotoVoter;
use App\Service\Application\FileDownloadHelper;
use App\Service\Photo\AlbumService;
use App\Service\Photo\PhotoService;
use App\Service\Photo\WeeklyPhotoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

use function in_array;
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
        private readonly FileDownloadHelper $fileDownloadHelper,
        private readonly SluggerInterface $slugger,
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
        // A member's "album" is the virtual set of photos they are tagged in (the {album} route value is their lidnr).
        if (AlbumType::Member === $type) {
            return $this->memberAlbum($album);
        }

        // A weekly "album" is the virtual set of one association year's photos of the week ({album} is the year).
        if (AlbumType::Weekly === $type) {
            return $this->weeklyAlbum($album);
        }

        // The body virtual albums are a later addition; only real albums are browsable for now.
        if (AlbumType::Regular !== $type) {
            throw new NotFoundHttpException();
        }

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
     * The photos a member is tagged in, each linking into its real album's viewer. A member is tagged in a bounded set,
     * so this renders server-side rather than through the windowed manifest the album gallery uses.
     */
    private function memberAlbum(int $lidnr): Response
    {
        $member = $this->memberRepository->find($lidnr)
            ?? throw new NotFoundHttpException();

        return $this->render(
            'photo/member.html.twig',
            [
                'member' => $member,
                'photos' => $this->photoRepository->getAlbumPhotos(new MemberAlbum($lidnr, $member)),
            ],
        );
    }

    /**
     * The photos of the week of one association year, each linking into its real album. A year with no photos of the
     * week does not exist, so it 404s.
     */
    private function weeklyAlbum(int $year): Response
    {
        if ([] === $this->weeklyPhotoService->getPhotosInYear($year)) {
            throw new NotFoundHttpException();
        }

        // The gallery itself fetches the year's photos through the weekly manifest (route above).
        return $this->render(
            'photo/weekly-album.html.twig',
            ['year' => $year],
        );
    }

    private function viewableAlbum(int $album): Album
    {
        return $this->albumService->findViewableAlbum($album)
            ?? throw new NotFoundHttpException();
    }
}
