<?php

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{JsonModel, ViewModel};
use Laminas\Mvc\I18n\Translator;
use Photo\Mapper\{
    Tag as TagMapper,
    Vote as VoteMapper,
};
use Photo\Service\AclService;
use User\Permissions\NotAllowedException;

class ApiController extends AbstractActionController
{
    private AclService $aclService;

    private Translator $translator;

    private TagMapper $tagMapper;

    private VoteMapper $voteMapper;

    public function __construct(
        AclService $aclService,
        Translator $translator,
        TagMapper $tagMapper,
        VoteMapper $voteMapper,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->tagMapper = $tagMapper;
        $this->voteMapper = $voteMapper;
    }

    /**
     * Retrieve a list of all photo's in an album.
     *
     * This API call is intended for external scripts. Like the AViCo TV screen
     * that needs a list of all photo's.
     */
    public function listAction(): JsonModel|ViewModel
    {
        $albumId = $this->params()->fromRoute('album_id');
        $album = $this->plugin('AlbumPlugin')->getAlbumAsArray($albumId);
        if (is_null($albumId)) {
            return $this->notFoundAction();
        }

        return new JsonModel($album);
    }

    public function detailsAction(): JsonModel
    {
        if (
            !$this->aclService->isAllowed('view', 'tag')
            && !$this->aclService->isAllowed('view', 'vote')
        ) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photo details'));
        }

        $photoId = $this->params()->fromRoute('photo_id');

        return new JsonModel([
            'tags' => $this->tagMapper->getTagsByPhoto($photoId),
            'voted' => null !== $this->voteMapper->findVote($photoId, $this->aclService->getIdentity()->getLidnr()),
        ]);
    }
}
