<?php

declare(strict_types=1);

namespace App\Controller\Photo;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Entity\User\Enums\UserRoles;
use App\Form\Photo\AlbumType;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Repository\Photo\WeeklyPhotoRepository;
use App\Service\Photo\AlbumAdminService;
use App\Service\Photo\AlbumService;
use App\Service\Photo\PhotoService;
use App\Service\Photo\PhotoUploadService;
use App\Service\Photo\WeeklyPhotoService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_filter;
use function array_keys;
use function array_map;
use function in_array;
use function intval;
use function mb_strlen;
use function trim;

#[IsGranted(
    attribute: UserRoles::Board->value,
    message: 'You are not allowed to administer photos.',
)]
#[Route(
    path: '/admin/photos',
    name: 'admin/photos/',
)]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly AlbumRepository $albumRepository,
        private readonly PhotoRepository $photoRepository,
        private readonly AlbumService $albumService,
        private readonly AlbumAdminService $albumAdminService,
        private readonly PhotoService $photoService,
        private readonly PhotoUploadService $photoUploadService,
        private readonly WeeklyPhotoRepository $weeklyPhotoRepository,
        private readonly WeeklyPhotoService $weeklyPhotoService,
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
        // Prod has many albums, so the overview shows one association year at a time, defaulting to the most recent.
        $albumsByYear = $this->albumService->getRootAlbumsByYear();
        $years = array_keys($albumsByYear);
        $selectedYear = null !== $year && in_array(
            $year,
            $years,
            true,
        )
            ? $year
            : ($years[0] ?? null);
        $albums = null === $selectedYear
            ? []
            : ($albumsByYear[$selectedYear] ?? []);

        return $this->render(
            'photo/admin/index.html.twig',
            [
                'year' => $selectedYear,
                'years' => $years,
                'albums' => $albums,
                'cardCounts' => $this->albumService->getCardCounts($albums),
            ],
        );
    }

    #[Route(
        path: '/weekly',
        name: 'weekly',
        methods: ['GET'],
    )]
    public function weekly(): Response
    {
        return $this->render(
            'photo/admin/weekly.html.twig',
            [
                'weeklyPhoto' => $this->weeklyPhotoRepository->getCurrentPhotoOfTheWeek(),
                'canHide' => $this->withinHideWindow(),
            ],
        );
    }

    #[Route(
        path: '/weekly/hide',
        name: 'weekly_hide',
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'photo_weekly_hide',
        tokenKey: '_csrf_token',
    )]
    public function hideWeekly(): Response
    {
        $weeklyPhoto = $this->weeklyPhotoRepository->getCurrentPhotoOfTheWeek();

        if (
            null !== $weeklyPhoto
            && !$weeklyPhoto->isHidden()
            && $this->withinHideWindow()
        ) {
            $this->weeklyPhotoService->hide($weeklyPhoto);

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('The photo of the week has been hidden.'),
            );
        }

        return $this->redirectToRoute('admin/photos/weekly');
    }

    /**
     * The board may hide the current photo of the week only on Monday before noon, so a photo that is being widely
     * shared that day can still be pulled but a settled week is left alone.
     */
    private function withinHideWindow(): bool
    {
        $now = new DateTime();

        return 1 === (int) $now->format('N')
            && 12 > (int) $now->format('G');
    }

    #[Route(
        path: '/albums/create',
        name: 'albums_create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function createAlbum(
        Request $request,
        #[MapQueryParameter]
        ?int $parent = null,
    ): Response {
        $album = new Album();
        // A sub-album is created from its parent's manage view (?parent=); otherwise it is a root album.
        if (null !== $parent) {
            $album->setParent($this->albumRepository->find($parent));
        }

        $form = $this->createForm(AlbumType::class, $album)->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'photo/admin/album-form.html.twig',
                [
                    'form' => $form,
                    'album' => null,
                ],
            );
        }

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The album has been created.'),
        );

        return $this->redirectToRoute(
            'admin/photos/album',
            ['album' => $album->getId()],
        );
    }

    #[Route(
        path: '/albums/{album}/edit',
        name: 'albums_edit',
        requirements: ['album' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function editAlbum(
        Album $album,
        Request $request,
    ): Response {
        $form = $this->createForm(AlbumType::class, $album)->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'photo/admin/album-form.html.twig',
                [
                    'form' => $form,
                    'album' => $album,
                ],
            );
        }

        $this->entityManager->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The album has been updated.'),
        );

        return $this->redirectToRoute(
            'admin/photos/album',
            ['album' => $album->getId()],
        );
    }

    #[Route(
        path: '/albums/{album}/delete',
        name: 'albums_delete',
        requirements: ['album' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"photo_album_delete-" ~ args["album"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function deleteAlbum(Album $album): Response
    {
        $this->albumAdminService->deleteAlbum($album);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The album has been deleted.'),
        );

        return $this->redirectToRoute('admin/photos/index');
    }

    #[Route(
        path: '/albums/{album}',
        name: 'album',
        requirements: ['album' => '\d+'],
        methods: ['GET'],
    )]
    public function album(Album $album): Response
    {
        return $this->render(
            'photo/admin/album.html.twig',
            ['album' => $album],
        );
    }

    /**
     * The viewer manifest for the album manage view. Same shape as the public one but reachable for drafts too, since
     * the manage view is board-only.
     */
    #[Route(
        path: '/albums/{album}/manifest',
        name: 'album_manifest',
        requirements: ['album' => '\d+'],
        methods: ['GET'],
    )]
    public function albumManifest(Album $album): JsonResponse
    {
        return new JsonResponse($this->photoService->getAlbumManifest($album));
    }

    /**
     * Album name search for the move-photos destination picker. Kept off the album page itself so a set of thousands of
     * albums is never loaded up front.
     */
    #[Route(
        path: '/albums/search',
        name: 'albums_search',
        methods: ['GET'],
    )]
    public function searchAlbums(
        #[MapQueryParameter]
        string $q = '',
    ): JsonResponse {
        $query = trim($q);
        if (mb_strlen($query) < 2) {
            return new JsonResponse([]);
        }

        return new JsonResponse(array_map(
            static function (Album $album): array {
                $parent = $album->getParent();

                return [
                    'id' => $album->getId(),
                    'label' => null === $parent
                        ? $album->getName()
                        : $parent->getName() . ' / ' . $album->getName(),
                ];
            },
            $this->albumRepository->searchForMove($query),
        ));
    }

    #[Route(
        path: '/albums/{album}/cover',
        name: 'album_cover',
        requirements: ['album' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: new Expression('"photo_album_cover-" ~ args["album"].getId()'),
        tokenKey: '_csrf_token',
    )]
    public function regenerateCover(Album $album): Response
    {
        $this->albumAdminService->regenerateCover($album);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The album cover is being regenerated.'),
        );

        return $this->redirectToRoute(
            'admin/photos/album',
            ['album' => $album->getId()],
        );
    }

    #[Route(
        path: '/albums/{album}/upload',
        name: 'album_upload',
        requirements: ['album' => '\d+'],
        methods: ['POST'],
    )]
    public function upload(
        Album $album,
        Request $request,
    ): JsonResponse {
        $files = array_filter(
            $request->files->all('photos'),
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        );

        $result = $this->photoUploadService->upload(
            $album,
            $files,
        );

        if ($result['created'] > 0) {
            $this->albumAdminService->updateDateRange($album);
        }

        return new JsonResponse($result);
    }

    #[Route(
        path: '/albums/{album}/photos/move',
        name: 'photos_move',
        requirements: ['album' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'photo_admin_bulk',
        tokenKey: '_csrf_token',
    )]
    public function movePhotos(
        Album $album,
        Request $request,
    ): Response {
        $destination = $this->albumRepository->find($request->request->getInt('destination'));
        $photos = $this->selectedPhotos($request);

        if (
            null === $destination
            || [] === $photos
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('Select photos and a destination album to move them.'),
            );

            return $this->redirectToRoute(
                'admin/photos/album',
                ['album' => $album->getId()],
            );
        }

        $this->albumAdminService->movePhotos(
            $photos,
            $destination,
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('The photos have been moved.'),
        );

        return $this->redirectToRoute(
            'admin/photos/album',
            ['album' => $album->getId()],
        );
    }

    #[Route(
        path: '/albums/{album}/photos/delete',
        name: 'photos_delete',
        requirements: ['album' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'photo_admin_bulk',
        tokenKey: '_csrf_token',
    )]
    public function deletePhotos(
        Album $album,
        Request $request,
    ): Response {
        $photos = $this->selectedPhotos($request);
        if ([] !== $photos) {
            $this->albumAdminService->deletePhotos($photos);

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('The photos have been deleted.'),
            );
        }

        return $this->redirectToRoute(
            'admin/photos/album',
            ['album' => $album->getId()],
        );
    }

    /**
     * @return Photo[]
     */
    private function selectedPhotos(Request $request): array
    {
        $ids = array_map(
            intval(...),
            $request->request->all('photos'),
        );

        return [] === $ids
            ? []
            : $this->photoRepository->findBy(['id' => $ids]);
    }
}
