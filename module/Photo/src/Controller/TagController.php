<?php

namespace Photo\Controller;

use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Photo\Service\{
    AclService,
    Photo as PhotoService,
};
use User\Permissions\NotAllowedException;

class TagController extends AbstractActionController
{
    private AclService $aclService;

    private Translator $translator;

    /**
     * @var PhotoService
     */
    private PhotoService $photoService;

    /**
     * TagController constructor.
     *
     * @param AclService $aclService
     * @param Translator $translator
     * @param PhotoService $photoService
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        PhotoService $photoService,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->photoService = $photoService;
    }

    public function addAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('add', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to add tags.'));
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];

        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $lidnr = $this->params()->fromRoute('lidnr');
            $tag = $this->photoService->addTag($photoId, $lidnr);

            if (null === $tag) {
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

        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];

        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $lidnr = $this->params()->fromRoute('lidnr');
            $result['success'] = $this->photoService->removeTag($photoId, $lidnr);
        }

        return new JsonModel($result);
    }
}
