<?php

declare(strict_types=1);

namespace Decision\Controller;

use Decision\Model\Enums\MeetingTypes;
use Decision\Service\AclService;
use Decision\Service\Decision as DecisionService;
use Laminas\Http\PhpEnvironment\Response as EnvironmentResponse;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use Throwable;
use User\Permissions\NotAllowedException;

use function array_merge;
use function array_merge_recursive;

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
                    $this->flashMessenger()->addSuccessMessage(
                        $this->translator->translate('Meeting minutes uploaded'),
                    );

                    return $this->redirect()->toRoute('decision_admin/minutes');
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
     * Document upload action.
     */
    public function documentAction(): ViewModel
    {
        $type = $this->params()->fromRoute('type');

        if (null !== $type) {
            $type = MeetingTypes::from($type);
        }

        $number = (int) $this->params()->fromRoute('number');

        $meetings = $this->decisionService->getMeetingsByType(MeetingTypes::ALV);
        $meetings = array_merge($meetings, $this->decisionService->getMeetingsByType(MeetingTypes::VV));

        if (
            0 === $number
            && null === $type
        ) {
            if (empty($meetings)) {
                return new ViewModel(['noMeetings' => true]);
            }

            $number = $meetings[0]->getNumber();
            $type = $meetings[0]->getType();
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
                'type' => $type,
                'success' => $success,
            ],
        );
    }

    public function renameDocumentAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('rename_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to rename meeting documents'),
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

                return $this->redirect()->toRoute(
                    'decision_admin/document',
                    [
                        'type' => $document->getMeeting()->getType()->value,
                        'number' => $document->getMeeting()->getNumber(),
                    ],
                );
            }

            return $this->notFoundAction();
        }

        return $this->notFoundAction();
    }

    public function deleteDocumentAction(): Response
    {
        if (!$this->aclService->isAllowed('delete_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete meeting documents.'),
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

        return $this->redirect()->toRoute('decision_admin/document');
    }

    public function changePositionDocumentAction(): mixed
    {
        /** @var Request $request */
        $request = $this->getRequest();
        /** @var EnvironmentResponse $response */
        $response = $this->getResponse();

        /** @var string|null $id */
        $id = $request->getPost('id');
        /** @var string|null $newPosition */
        $newPosition = $request->getPost('position');

        if (
            null === $id
            || null === $newPosition
        ) {
            return $response
                ->setStatusCode(Response::STATUS_CODE_400)
                ->setContent(Json::encode(['error' => 'Document ID or position not provided']));
        }

        $id = (int) $id;
        $newPosition = (int) $newPosition;

        if (
            0 >= $id
            || 0 > $newPosition
        ) {
            return $response
                ->setStatusCode(Response::STATUS_CODE_400)
                ->setContent(Json::encode(['error' => 'Invalid document ID or position']));
        }

        try {
            $this->decisionService->changePositionDocument($id, $newPosition);

            return $response->setStatusCode(Response::STATUS_CODE_204);
        } catch (Throwable $e) {
            return $response
                ->setStatusCode(Response::STATUS_CODE_500)
                ->setContent(Json::encode(['error' => $e->getMessage()]));
        }
    }

    public function authorizationsAction(): ViewModel
    {
        $meetings = $this->decisionService->getMeetingsByType(MeetingTypes::ALV);
        $number = (int) $this->params()->fromRoute('number');

        if (
            0 === $number
            && !empty($meetings)
        ) {
            $number = $meetings[0]->getNumber();
        }

        return new ViewModel(
            [
                'meetings' => $meetings,
                ...$this->decisionService->getAllAuthorizations($number),
                'number' => $number,
            ],
        );
    }
}
