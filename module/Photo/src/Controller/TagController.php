<?php

declare(strict_types=1);

namespace Photo\Controller;

use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use Photo\Service\AclService;
use Photo\Service\Photo as PhotoService;
use User\Permissions\NotAllowedException;

class TagController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly PhotoService $photoService,
    ) {
    }

    public function addAction(): JsonModel
    {
        if (!$this->aclService->isAllowed('add', 'tag')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add tags'));
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];

        if ($request->isPost()) {
            $photoId = (int) $this->params()->fromRoute('photo_id');
            $type = $this->params()->fromRoute('type');
            $id = (int) $this->params()->fromRoute('id');
            $tag = $this->photoService->addTag($photoId, $type, $id);

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
            throw new NotAllowedException($this->translator->translate('You are not allowed to remove tags'));
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];

        if ($request->isPost()) {
            $photoId = (int) $this->params()->fromRoute('photo_id');
            $type = $this->params()->fromRoute('type');
            $id = (int) $this->params()->fromRoute('id');
            $result['success'] = $this->photoService->removeTag($photoId, $type, $id);
        }

        return new JsonModel($result);
    }
}
