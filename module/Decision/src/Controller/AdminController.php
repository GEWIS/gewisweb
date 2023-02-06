<?php

namespace Decision\Controller;

use Decision\Model\Enums\MeetingTypes;
use Decision\Service\{
    AclService,
    Decision as DecisionService,
};
use Laminas\Http\{
    PhpEnvironment\Response as EnvironmentResponse,
    Request,
    Response,
};
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

/**
 * @method FlashMessenger flashMessenger()
 */
class AdminController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly DecisionService $decisionService,
    ) {
    }

    /**
     * Minutes upload action.
     */
    public function minutesAction(): ViewModel|Response
    {
        $form = $this->decisionService->getMinutesForm();

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );
            $form->setData($post);

            if ($form->isValid()) {
                if ($this->decisionService->uploadMinutes($form->getData())) {
                    $this->flashMessenger()->addSuccessMessage($this->translator->translate('Meeting minutes uploaded'));

                    return $this->redirect()->toRoute('admin_decision/minutes');
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }

    /**
     * Document upload action.
     */
    public function documentAction(): ViewModel
    {
        $type = $this->params()->fromRoute('type');

        if (null !== $type) {
            $type = MeetingTypes::from($type);
        }

        $number = $this->params()->fromRoute('number');

        $meetings = $this->decisionService->getMeetingsByType(MeetingTypes::ALV);
        $meetings = array_merge($meetings, $this->decisionService->getMeetingsByType(MeetingTypes::VV));

        if (
            null === $number
            && null === $type
        ) {
            if (!empty($meetings)) {
                $number = $meetings[0]->getNumber();
                $type = $meetings[0]->getType();
            } else {
                return new ViewModel(['noMeetings' => true]);
            }
        }

        $form = $this->decisionService->getDocumentForm();

        /** @var Request $request */
        $request = $this->getRequest();

        $success = false;
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray(),
            );
            $form->setData($post);

            if ($form->isValid()) {
                if ($this->decisionService->uploadDocument($form->getData())) {
                    $success = true;
                }
            }
        }

        $meeting = $this->decisionService->getMeeting($type, $number);

        return new ViewModel(
            [
                'form' => $form,
                'meetings' => $meetings,
                'meeting' => $meeting,
                'number' => $number,
                'success' => $success,
                'reorderDocumentForm' => $this->decisionService->getReorderDocumentForm(),
            ]
        );
    }

    public function renameDocumentAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('rename_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to rename meeting documents')
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $document = $this->decisionService->getMeetingDocument((int) $this->params()->fromRoute('document_id'));

            if (null !== $document) {
                $form = $this->decisionService->getDocumentForm();
                $form->setData($request->getPost()->toArray());
                $form->setValidationGroup(['name']);

                if ($form->isValid()) {
                    $this->decisionService->renameDocument(
                        $document,
                        $form->getData(),
                    );
                }

                return $this->redirect()->toRoute('admin_decision/document');
            }

            return $this->notFoundAction();
        }

        return $this->notFoundAction();
    }

    public function deleteDocumentAction(): Response
    {
        if (!$this->aclService->isAllowed('delete_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete meeting documents.')
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $document = $this->decisionService->getMeetingDocument((int) $this->params()->fromRoute('document_id'));

            if (null !== $document) {
                $this->decisionService->deleteDocument($document);
            }
        }

        return $this->redirect()->toRoute('admin_decision/document');
    }

    public function changePositionDocumentAction(): mixed
    {
        /** @var Request $request */
        $request = $this->getRequest();
        /** @var EnvironmentResponse $response */
        $response = $this->getResponse();

        $form = $this->decisionService->getReorderDocumentForm()->setData($request->getPost()->toArray());

        if (!$form->isValid()) {
            return $response
                ->setStatusCode(Response::STATUS_CODE_400) // Bad Request
                ->setContent(Json::encode($form->getMessages()));
        }

        $data = $form->getData();
        $id = $data['document'];
        $moveDown = 'down' === $data['direction'];

        // Update ordering document
        $this->decisionService->changePositionDocument($id, $moveDown);

        return $response->setStatusCode(Response::STATUS_CODE_204); // No Content (OK)
    }

    public function authorizationsAction(): ViewModel
    {
        $meetings = $this->decisionService->getMeetingsByType(MeetingTypes::ALV);
        $number = $this->params()->fromRoute('number');

        if (null === $number && !empty($meetings)) {
            $number = $meetings[0]->getNumber();
        }

        $authorizations = [
            'valid' => [],
            'revoked' => [],
        ];
        if (null !== $number) {
            $authorizations = $this->decisionService->getAllAuthorizations($number);
        }

        return new ViewModel(
            [
                'meetings' => $meetings,
                ...$authorizations,
                'number' => $number,
            ]
        );
    }
}
