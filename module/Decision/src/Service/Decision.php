<?php

namespace Decision\Service;

use Application\Service\{
    Email as EmailService,
    FileStorage as FileStorageService,
};
use Decision\Form\{
    Authorization as AuthorizationForm,
    Document as DocumentForm,
    Notes as NotesForm,
    ReorderDocument as ReorderDocumentForm,
    SearchDecision as SearchDecisionForm,
};
use Decision\Mapper\{
    Authorization as AuthorizationMapper,
    Decision as DecisionMapper,
    Member as MemberMapper,
    Meeting as MeetingMapper,
};
use Decision\Model\{
    Authorization as AuthorizationModel,
    Meeting as MeetingModel,
    MeetingDocument as MeetingDocumentModel,
    MeetingNotes as MeetingNotesModel,
};
use Doctrine\ORM\{
    NonUniqueResultException,
    Exception\ORMException,
    PersistentCollection,
};
use Exception;
use InvalidArgumentException;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

/**
 * Decision service.
 */
class Decision
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var FileStorageService
     */
    private FileStorageService $storageService;

    /**
     * @var EmailService
     */
    private EmailService $emailService;

    /**
     * @var MemberMapper
     */
    private MemberMapper $memberMapper;

    /**
     * @var MeetingMapper
     */
    private MeetingMapper $meetingMapper;

    /**
     * @var DecisionMapper
     */
    private DecisionMapper $decisionMapper;

    /**
     * @var AuthorizationMapper
     */
    private AuthorizationMapper $authorizationMapper;

    /**
     * @var NotesForm
     */
    private NotesForm $notesForm;

    /**
     * @var DocumentForm
     */
    private DocumentForm $documentForm;

    /**
     * @var ReorderDocumentForm
     */
    private ReorderDocumentForm $reorderDocumentForm;

    /**
     * @var SearchDecisionForm
     */
    private SearchDecisionForm $searchDecisionForm;

    /**
     * @var AuthorizationForm
     */
    private AuthorizationForm $authorizationForm;

    /**
     * @param AclService $aclService
     * @param Translator $translator
     * @param FileStorageService $storageService
     * @param EmailService $emailService
     * @param MemberMapper $memberMapper
     * @param MeetingMapper $meetingMapper
     * @param DecisionMapper $decisionMapper
     * @param AuthorizationMapper $authorizationMapper
     * @param NotesForm $notesForm
     * @param DocumentForm $documentForm
     * @param ReorderDocumentForm $reorderDocumentForm
     * @param SearchDecisionForm $searchDecisionForm
     * @param AuthorizationForm $authorizationForm
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        FileStorageService $storageService,
        EmailService $emailService,
        MemberMapper $memberMapper,
        MeetingMapper $meetingMapper,
        DecisionMapper $decisionMapper,
        AuthorizationMapper $authorizationMapper,
        NotesForm $notesForm,
        DocumentForm $documentForm,
        ReorderDocumentForm $reorderDocumentForm,
        SearchDecisionForm $searchDecisionForm,
        AuthorizationForm $authorizationForm,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->storageService = $storageService;
        $this->emailService = $emailService;
        $this->memberMapper = $memberMapper;
        $this->meetingMapper = $meetingMapper;
        $this->decisionMapper = $decisionMapper;
        $this->authorizationMapper = $authorizationMapper;
        $this->notesForm = $notesForm;
        $this->documentForm = $documentForm;
        $this->reorderDocumentForm = $reorderDocumentForm;
        $this->searchDecisionForm = $searchDecisionForm;
        $this->authorizationForm = $authorizationForm;
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Get all meetings.
     *
     * @param int|null $limit The amount of meetings to retrieve, default is all
     *
     * @return array Of all meetings
     */
    public function getMeetings(?int $limit = null): array
    {
        if (!$this->aclService->isAllowed('list_meetings', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list meetings.'));
        }

        return $this->meetingMapper->findAllMeetings($limit);
    }

    /**
     * Get past meetings.
     *
     * @param int|null $limit The amount of meetings to retrieve, default is all
     * @param string|null $type Constraint on the type of the meeting, default is none
     *
     * @return array Of all meetings
     */
    public function getPastMeetings(
        ?int $limit = null,
        ?string $type = null,
    ): array {
        if (!$this->aclService->isAllowed('list_meetings', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list meetings.'));
        }

        return $this->meetingMapper->findPast($limit, $type);
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getMeetingsByType(string $type): array
    {
        if (!$this->aclService->isAllowed('list_meetings', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list meetings.'));
        }

        return $this->meetingMapper->findByType($type);
    }

    /**
     * Get information about one meeting.
     *
     * @param string|null $type
     * @param int|null $number
     *
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    public function getMeeting(
        ?string $type,
        ?int $number,
    ): ?MeetingModel {
        if (!$this->aclService->isAllowed('view', 'meeting')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view meetings.'));
        }

        return $this->meetingMapper->findMeeting($type, $number);
    }

    /**
     * Returns the latest upcoming AV or null if there is none.
     *
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    public function getLatestAV(): ?MeetingModel
    {
        return $this->meetingMapper->findLatestAV();
    }

    /**
     * Returns the closest upcoming meeting for members.
     *
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    public function getUpcomingMeeting(): ?MeetingModel
    {
        return $this->meetingMapper->findUpcomingMeeting();
    }

    /**
     * Get meeting documents corresponding to a certain id.
     *
     * @param int $id
     *
     * @return MeetingDocumentModel|null
     * @throws ORMException
     */
    public function getMeetingDocument(int $id): ?MeetingDocumentModel
    {
        return $this->meetingMapper->findDocument($id);
    }

    /**
     * Returns a download for a meeting document.
     *
     * @param MeetingDocumentModel $meetingDocument
     *
     * @return Stream|null
     */
    public function getMeetingDocumentDownload(MeetingDocumentModel $meetingDocument): ?Stream
    {
        if (!$this->aclService->isAllowed('view_documents', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view meeting documents.')
            );
        }

        $path = $meetingDocument->getPath();
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fileName = $meetingDocument->getName() . '.' . $extension;

        return $this->storageService->downloadFile($path, $fileName);
    }

    /**
     * Returns a download for meeting notes.
     *
     * @param MeetingModel $meeting
     *
     * @return Stream|null
     */
    public function getMeetingNotesDownload(MeetingModel $meeting): ?Stream
    {
        if (!$this->aclService->isAllowed('view_notes', 'meeting')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view meeting notes.'));
        }

        if (is_null($meeting->getNotes())) {
            return null;
        }

        $path = $meeting->getNotes()->getPath();
        $fileName = $meeting->getType() . '-' . $meeting->getNumber() . '.pdf';

        return $this->storageService->downloadFile($path, $fileName);
    }

    /**
     * Upload meeting notes.
     *
     * @param array $data
     *
     * @return bool If uploading was a success
     * @throws Exception
     */
    public function uploadNotes(array $data): bool
    {
        $parts = explode('/', $data['meeting']);
        $meeting = $this->getMeeting($parts[0], intval($parts[1]));
        $path = $this->storageService->storeUploadedFile($data['upload']);

        $meetingNotes = $meeting->getNotes();
        if (is_null($meetingNotes)) {
            $meetingNotes = new MeetingNotesModel();
            $meetingNotes->setMeeting($meeting);
        }

        $meetingNotes->setPath($path);

        $mapper = $this->decisionMapper;
        $mapper->persist($meetingNotes);

        return true;
    }

    /**
     * Upload a meeting document.
     *
     * @param array $data
     *
     * @return bool If uploading was a success
     * @throws Exception
     */
    public function uploadDocument(array $data): bool
    {
        $path = $this->storageService->storeUploadedFile($data['upload']);

        $meeting = explode('/', $data['meeting']);
        $meeting = $this->getMeeting($meeting[0], intval($meeting[1]));

        $document = new MeetingDocumentModel();
        $document->setPath($path);
        $document->setName($data['name']);
        $document->setMeeting($meeting);

        // Determine document's position in ordering
        $maxPosition = $this->meetingMapper->findMaxDocumentPosition($meeting);
        $position = is_null($maxPosition) ? 0 : ++$maxPosition; // NULL if meeting doesn't have documents yet

        $document->setDisplayPosition($position);

        $this->meetingMapper->persist($document);

        return true;
    }

    /**
     * @param array $data
     *
     * @throws ORMException
     */
    public function deleteDocument(array $data): void
    {
        if (!$this->aclService->isAllowed('delete_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete meeting documents.')
            );
        }

        // TODO: The actual file is never deleted.
        $document = $this->getMeetingDocument($data['document']);
        $this->meetingMapper->remove($document);
    }

    /**
     * Changes a document's position in the ordering.
     *
     * The basic flow is (1) retrieve documents, (2) swap document positions,
     * then (3) persist position. Unfortunately, I have to update the positions
     * of all documents related to a meeting because of legacy. Old documents
     * don't have a position yet, so they are set to 0 by default.
     *
     * FUTURE: When documents have display positions, simplify the code by only
     * mutating two rows.
     *
     * @param int $id Document ID
     * @param bool $moveDown If the document should be moved down in the ordering, defaults to TRUE
     *
     * @throws NotAllowedException
     * @throws InvalidArgumentException If the document doesn't exist
     */
    public function changePositionDocument(
        int $id,
        bool $moveDown = true,
    ): void {
        $errorMessage = 'You are not allowed to modify meeting documents.';

        $this->isAllowedOrFail('upload_document', 'meeting', $errorMessage);

        // Documents are ordered because of @OrderBy annotation on the relation
        /** @var PersistentCollection $documents */
        $documents = $this->meetingMapper
            ->findDocumentOrFail($id)
            ->getMeeting()
            ->getDocuments();

        // Create data structure to derive ordering, key is position and value
        // is document ID
        $ordering = $documents->map(function (MeetingDocumentModel $document) {
            return $document->getId();
        });

        $oldPosition = $ordering->indexOf($id);
        $newPosition = (true === $moveDown) ? ($oldPosition + 1) : ($oldPosition - 1);

        // Do nothing if the document is already at the top/bottom
        if ($newPosition < 0 || $newPosition > ($ordering->count() - 1)) {
            return;
        }

        // Swap positions
        $ordering->set($oldPosition, $ordering->get($newPosition));
        $ordering->set($newPosition, $id);

        // Persist new positions
        $documents->map(function (MeetingDocumentModel $document) use ($ordering): void {
            $position = $ordering->indexOf($document->getId());

            $document->setDisplayPosition($position);

            $this->meetingMapper->persist($document);
        });
    }

    /**
     * Search for decisions.
     *
     * @param array $data Search data
     *
     * @return array|null Search results
     */
    public function search(array $data): ?array
    {
        return $this->decisionMapper->search($data['query']);
    }

    /**
     * Retrieves all authorizations for the given meeting number.
     *
     * @param int $meetingNumber
     *
     * @return array
     */
    public function getAllAuthorizations(int $meetingNumber): array
    {
        if (!$this->aclService->isAllowed('view_all', 'authorization')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view all authorizations.')
            );
        }

        return $this->authorizationMapper->findNotRevoked($meetingNumber);
    }

    /**
     * Gets the authorization of the current user for the given meeting.
     *
     * @param MeetingModel $meeting
     *
     * @return AuthorizationModel|null
     */
    public function getUserAuthorization(MeetingModel $meeting): ?AuthorizationModel
    {
        if (!$this->aclService->isAllowed('view_own', 'authorization')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view authorizations.'));
        }

        $member = $this->aclService->getIdentityOrThrowException()->getMember();

        return $this->authorizationMapper->findUserAuthorization($meeting->getNumber(), $member);
    }

    /**
     * @param array $data
     *
     * @return AuthorizationModel|false
     * @throws ORMException
     */
    public function createAuthorization(array $data): AuthorizationModel|bool
    {
        $authorizer = $this->aclService->getIdentityOrThrowException()->getMember();
        $recipient = $this->memberMapper->findByLidnr($data['recipient']);

        if (
            null === $recipient
            || $recipient->getLidnr() === $authorizer->getLidnr()
        ) {
            return false;
        }

        $meeting = $this->getLatestAV();
        if (null === $meeting) {
            return false;
        }

        // You cannot authorize more than one person.
        if (null !== $this->getUserAuthorization($meeting)) {
            return false;
        }

        $authorization = new AuthorizationModel();

        $authorization->setAuthorizer($authorizer);
        $authorization->setRecipient($recipient);
        $authorization->setMeetingNumber($meeting->getNumber());
        $this->authorizationMapper->persist($authorization);

        // Send an email to the recipient
        $this->emailService->sendEmailAsUserToUser(
            $recipient,
            'email/authorization_received',
            'Machtiging ontvangen | Authorization received',
            ['authorization' => $authorization],
            $authorizer,
        );

        // Send a confirmation email to the authorizing member
        $this->emailService->sendEmailAsUserToUser(
            $authorizer,
            'email/authorization_sent',
            'Machtiging verstuurd | Authorization sent',
            ['authorization' => $authorization],
            $recipient,
        );

        return $authorization;
    }

    /**
     * Get the Notes form.
     *
     * @return NotesForm
     */
    public function getNotesForm(): NotesForm
    {
        if (!$this->aclService->isAllowed('upload_notes', 'meeting')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to upload notes.'));
        }

        return $this->notesForm->setMeetings($this->meetingMapper->findAllMeetings());
    }

    /**
     * Get the Document form.
     *
     * @return DocumentForm
     */
    public function getDocumentForm(): DocumentForm
    {
        if (!$this->aclService->isAllowed('upload_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to upload meeting documents.')
            );
        }

        return $this->documentForm;
    }

    /**
     * @return ReorderDocumentForm
     */
    public function getReorderDocumentForm(): ReorderDocumentForm
    {
        $errorMessage = 'You are not allowed to modify meeting documents.';

        $this->isAllowedOrFail('upload_document', 'meeting', $errorMessage);

        return $this->reorderDocumentForm;
    }

    /**
     * Get the SearchDecision form.
     *
     * @return SearchDecisionForm
     */
    public function getSearchDecisionForm(): SearchDecisionForm
    {
        return $this->searchDecisionForm;
    }

    /**
     * Get the Authorization form.
     *
     * @return AuthorizationForm
     */
    public function getAuthorizationForm(): AuthorizationForm
    {
        if (!$this->aclService->isAllowed('create', 'authorization')) {
            throw new NotAllowedException($this->translator->translate('You are not authorize people.'));
        }

        return $this->authorizationForm;
    }

    /**
     * Returns whether the current role is allowed to view files.
     *
     * @return bool
     */
    public function isAllowedToBrowseFiles(): bool
    {
        return $this->aclService->isAllowed('browse', 'files');
    }

    /**
     * Checks the user's permission.
     *
     * @param string $operation
     * @param string $resource
     * @param string $errorMessage English error message
     *
     * @throws NotAllowedException If the user doesn't have permission
     */
    private function isAllowedOrFail(
        string $operation,
        string $resource,
        string $errorMessage,
    ): void {
        if (!$this->aclService->isAllowed($operation, $resource)) {
            throw new NotAllowedException($this->translator->translate($errorMessage));
        }
    }
}
