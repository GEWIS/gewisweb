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
use App\Service\Photo\AlbumAdminService;
use App\Service\Photo\AlbumService;
use App\Service\Photo\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_filter;
use function array_map;
use function intval;

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
        private readonly PhotoUploadService $photoUploadService,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render(
            'photo/admin/index.html.twig',
            ['albumsByYear' => $this->albumService->getRootAlbumsByYear()],
        );
    }

    #[Route(
        path: '/albums/create',
        name: 'albums_create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function createAlbum(Request $request): Response
    {
        $album = new Album();
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
        $form = $this->createForm(AlbumType::class, $album, ['album' => $album])->handleRequest($request);

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
            [
                'album' => $album,
                'photos' => $this->photoRepository->getAlbumPhotos($album),
                'albums' => $this->albumRepository->findBy(
                    [],
                    ['name' => 'ASC'],
                ),
            ],
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

        return new JsonResponse($this->photoUploadService->upload($album, $files));
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

        foreach ($photos as $photo) {
            $this->albumAdminService->movePhoto(
                $photo,
                $destination,
            );
        }

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
