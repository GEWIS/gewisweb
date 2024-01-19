<?php

declare(strict_types=1);

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote as VoteMapper;
use Photo\Service\AclService;
use Photo\Service\Album as AlbumService;
use User\Permissions\NotAllowedException;

class ApiController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly TagMapper $tagMapper,
        private readonly VoteMapper $voteMapper,
        private readonly AlbumService $albumService,
    ) {
    }

    /**
     * Retrieve a list of all photos in an album.
     *
     * This API call is intended for external scripts. Like the AViCo TV screen
     * that needs a list of all photos.
     */
    public function listAction(): JsonModel|ViewModel
    {
        $albumId = (int) $this->params()->fromRoute('album_id');
        $album = $this->albumService->getAlbum($albumId);

        if (null === $album) {
            return $this->notFoundAction();
        }

        $albumArray = $album->toArrayWithChildren();

        $photos = $albumArray['photos'];
        $albums = $albumArray['children'];

        $albumArray['photos'] = [];
        $albumArray['children'] = [];

        return new JsonModel([
            'album' => $albumArray,
            'photos' => $photos,
            'albums' => $albums,
        ]);
    }

    public function detailsAction(): JsonModel
    {
        if (
            !$this->aclService->isAllowed('view', 'tag')
            && !$this->aclService->isAllowed('view', 'vote')
        ) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photo details'));
        }

        $photoId = (int) $this->params()->fromRoute('photo_id');

        return new JsonModel([
            'tags' => $this->tagMapper->getTagsByPhoto($photoId),
            'voted' => null !== $this->voteMapper->findVote($photoId, $this->aclService->getUserIdentity()->getLidnr()),
        ]);
    }
}
