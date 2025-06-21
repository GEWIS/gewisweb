<?php

declare(strict_types=1);

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use Override;
use Photo\Service\AclService;
use Photo\Service\Album as AlbumService;
use Photo\Service\Photo as PhotoService;
use User\Permissions\NotAllowedException;

class AlbumController extends AbstractActionController
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
     * Shows a page with all photos in an album, the album is either an actual
     * album or a member's album.
     */
    #[Override]
    public function indexAction(): ViewModel
    {
        $albumId = (int) $this->params()->fromRoute('album_id');
        $albumType = $this->params()->fromRoute('album_type');

        $album = $this->albumService->getAlbum($albumId, $albumType);
        if (null === $album) {
            return $this->notFoundAction();
        }

        if (
            (
                null === $album->getStartDateTime()
                || null === $album->getEndDateTime()
                || !$album->isPublished()
            )
            && !$this->aclService->isAllowed('nodate', 'album')
        ) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view albums without dates'),
            );
        }

        $hasRecentVote = $this->photoService->hasRecentVote();

        return new ViewModel(
            [
                'album' => $album,
                'basedir' => '/',
                'config' => $this->photoConfig,
                'hasRecentVote' => $hasRecentVote,
            ],
        );
    }
}
