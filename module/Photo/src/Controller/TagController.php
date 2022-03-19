<?php

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Photo\Mapper\Tag as TagMapper;
use Photo\Service\{
    AclService,
    Photo as PhotoService,
};
use User\Permissions\NotAllowedException;

class TagController extends AbstractActionController
{
    private AclService $aclService;

    private Translator $translator;

    private TagMapper $tagMapper;

    /**
     * @var PhotoService
     */
    private PhotoService $photoService;

    /**
     * TagController constructor.
     *
     * @param PhotoService $photoService
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        TagMapper $tagMapper,
        PhotoService $photoService,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->tagMapper = $tagMapper;
        $this->photoService = $photoService;
    }

    public function addAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('add', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to add tags.'));
        }

        $request = $this->getRequest();
        $result = [];
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $lidnr = $this->params()->fromRoute('lidnr');
            $tag = $this->photoService->addTag($photoId, $lidnr);
            if (is_null($tag)) {
                $result['success'] = false;
            } else {
                $result['success'] = true;
                $result['tag'] = $tag->toArray();
            }
        }

        return new JsonModel($result);
    }

    public function removeAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('remove', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to remove tags.'));
        }

        $request = $this->getRequest();
        $result = [];
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $lidnr = $this->params()->fromRoute('lidnr');
            $result['success'] = $this->photoService->removeTag($photoId, $lidnr);
        }

        return new JsonModel($result);
    }

    public function listAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('view', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view tags.'));
        }

        return new JsonModel($this->tagMapper->getTagsByPhoto($this->params()->fromRoute('photo_id')));
    }
}
