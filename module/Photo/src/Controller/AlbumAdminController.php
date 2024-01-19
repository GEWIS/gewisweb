<?php

declare(strict_types=1);

namespace Photo\Controller;

use DateTime;
use Decision\Model\AssociationYear;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Photo\Service\AclService;
use Photo\Service\Admin as AdminService;
use Photo\Service\Album as AlbumService;
use Throwable;
use User\Permissions\NotAllowedException;

class AlbumAdminController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly AdminService $adminService,
        private readonly AlbumService $albumService,
        private readonly array $photoConfig,
    ) {
    }

    /**
     * Retrieves the main photo admin index page.
     */
    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer albums'));
        }

        $years = $this->albumService->getAlbumYears(false);

        return new ViewModel(
            [
                'years' => $years,
                'year' => AssociationYear::fromDate(new DateTime())->getYear(),
            ],
        );
    }

    /**
     * Show a specific album.
     */
    public function viewAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer albums'));
        }

        $albumId = (int) $this->params()->fromRoute('album_id');
        $album = $this->albumService->getAlbum($albumId);

        if (null === $album) {
            return $this->notFoundAction();
        }

        $albumToCheck = $album;
        $startDateTime = null;
        while (null !== $albumToCheck) {
            // Recursively get start datetime of parent album if null.
            if (null !== ($parentStartDateTime = $albumToCheck->getStartDateTime())) {
                $startDateTime = $parentStartDateTime;
            }

            $albumToCheck = $albumToCheck->getParent();
        }

        if (null !== $startDateTime) {
            $year = AssociationYear::fromDate($startDateTime)->getYear();
        } else {
            $year = null;
        }

        return new ViewModel([
            'album' => $album,
            'config' => $this->photoConfig,
            'year' => $year,
        ]);
    }

    /**
     * Show all albums in a specific year.
     */
    public function yearAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer albums'));
        }

        $year = (int) $this->params()->fromRoute('year');

        return new ViewModel([
            'albums' => $this->albumService->getAlbumsByYear($year, false),
            'years' => $this->albumService->getAlbumYears(false),
            'year' => $year,
        ]);
    }

    /**
     * Show all albums that do not have a date, most of these will be recently created albums.
     */
    public function undatedAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('nodate', 'album')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view albums without dates'),
            );
        }

        return new ViewModel([
            'albums' => $this->albumService->getAlbumsWithoutDate(),
            'years' => $this->albumService->getAlbumYears(false),
        ]);
    }

    /**
     * Retrieves the album creation form and saves data if needed.
     */
    public function createAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create albums'));
        }

        $form = $this->albumService->getAlbumForm();

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $albumId = (int) $this->params()->fromRoute('album_id');
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if (null !== ($album = $this->albumService->createAlbum($albumId, $form->getData()))) {
                    return $this->redirect()->toRoute('admin_photo/album', ['album_id' => $album->getId()]);
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ],
        );
    }

    /**
     * Retrieves the album editing form and saves changes.
     */
    public function editAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit albums'));
        }

        $albumId = (int) $this->params()->fromRoute('album_id');
        $form = $this->albumService->getAlbumForm($albumId);

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->albumService->updateAlbum()) {
                    return $this->redirect()->toRoute('admin_photo/album', ['album_id' => $albumId]);
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
                'album' => $form->getObject(),
            ],
        );
    }

    public function addAction(): ViewModel
    {
        $this->adminService->checkUploadAllowed();

        $albumId = (int) $this->params()->fromRoute('album_id');
        $album = $this->albumService->getAlbum($albumId);

        return new ViewModel(
            [
                'album' => $album,
                'year' => AssociationYear::fromDate($album->getStartDateTime())->getYear(),
            ],
        );
    }

    /**
     * Uploads an image file and adds it to an album.
     */
    public function uploadAction(): JsonModel
    {
        $this->adminService->checkUploadAllowed();

        /** @var Request $request */
        $request = $this->getRequest();

        $result = [];
        $result['success'] = false;
        if ($request->isPost()) {
            $albumId = (int) $this->params()->fromRoute('album_id');
            $album = $this->albumService->getAlbum($albumId);

            try {
                $this->adminService->upload($request->getFiles()->toArray(), $album);
                $result['success'] = true;
            } catch (Throwable $e) {
                $this->getResponse()->setStatusCode(500);
                $result['error'] = $e->getMessage();
            }
        }

        return new JsonModel($result);
    }

    /**
     * Moves the album by setting the parent album to another album.
     */
    public function moveAction(): JsonModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        $result = [];
        if ($request->isPost()) {
            $albumId = (int) $this->params()->fromRoute('album_id');
            $parentId = (int) $request->getPost()['parent_id'];

            if (0 === $parentId) {
                $parentId = null;
            }

            $result['success'] = $this->albumService->moveAlbum($albumId, $parentId);
        }

        return new JsonModel($result);
    }

    /**
     * Deletes the album.
     */
    public function deleteAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('delete', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete albums'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        $albumId = (int) $this->params()->fromRoute('album_id');
        if ($request->isPost()) {
            $this->albumService->deleteAlbum($albumId);
        }

        return new JsonModel([]);
    }

    /**
     * Regenerates the cover photo for the album.
     */
    public function coverAction(): JsonModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $albumId = (int) $this->params()->fromRoute('album_id');
            if (null !== ($cover = $this->albumService->generateAlbumCover($albumId))) {
                return new JsonModel([
                    'success' => true,
                    'coverPath' => $cover,
                ]);
            }
        }

        return new JsonModel(['success' => false]);
    }
}
