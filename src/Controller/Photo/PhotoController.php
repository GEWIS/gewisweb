<?php

declare(strict_types=1);

namespace App\Controller\Photo;

use App\Entity\Photo\Album;
use App\Entity\Photo\Enums\AlbumType;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Photo\PhotoRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Security\Photo\PhotoVoter;
use App\Service\Application\FileDownloadHelper;
use App\Service\Photo\AlbumService;
use App\Service\Photo\PhotoService;
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
            ['weeklyPhoto' => $this->weeklyPhotoRepository->getCurrentPhotoOfTheWeek()],
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
        // The member, weekly and body virtual albums are a later addition; only real albums are browsable for now.
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

    private function viewableAlbum(int $album): Album
    {
        return $this->albumService->findViewableAlbum($album)
            ?? throw new NotFoundHttpException();
    }
}
