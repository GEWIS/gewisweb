<?php

declare(strict_types=1);

namespace Photo\Controller;

use DateTime;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Photo\Service\AclService;
use Photo\Service\Album as AlbumService;
use Photo\Service\Photo as PhotoService;
use User\Permissions\NotAllowedException;

use function intval;

class PhotoAdminController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly AlbumService $albumService,
        private readonly PhotoService $photoService,
        private readonly array $photoConfig,
    ) {
    }

    /**
     * Places a photo in another album.
     */
    public function moveAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('move', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to move photos'));
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];

        if ($request->isPost()) {
            $photoId = (int) $this->params()->fromRoute('photo_id');
            $albumId = intval($request->getPost()['album_id']);
            $result['success'] = $this->albumService->movePhoto($photoId, $albumId);
        }

        return new JsonModel($result);
    }

    /**
     * Removes a photo from an album and deletes it.
     */
    public function deleteAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('delete', 'photo')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete photos'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        $result = [];
        if ($request->isPost()) {
            $photoId = (int) $this->params()->fromRoute('photo_id');
            $result['success'] = $this->photoService->deletePhoto($photoId);
        }

        return new JsonModel($result);
    }

    public function weeklyAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'photo')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to hide the photo of the week'),
            );
        }

        $potw = $this->photoService->getCurrentPhotoOfTheWeek();
        if (null === $potw) {
            return new ViewModel([
                'noPhoto' => true,
            ]);
        }

        if ($potw->isHidden()) {
            return new ViewModel([
                'alreadyHidden' => true,
            ]);
        }

        $now = new DateTime('now');
        if (
            1 !== (int) $now->format('N')
            || 12 <= (int) $now->format('G')
        ) {
            return new ViewModel([
                'wrongTime' => true,
            ]);
        }

        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->photoService->hidePhotoOfTheWeek($potw);

            return $this->redirect()->toRoute('admin_photo/weekly');
        }

        return new ViewModel([
            'potw' => $potw,
            'config' => $this->photoConfig,
        ]);
    }
}
