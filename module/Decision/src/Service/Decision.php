<?php

declare(strict_types=1);

namespace Decision\Service;

use DateTime;
use Application\Service\{
    Email as EmailService,
    FileStorage as FileStorageService,
};
use Decision\Form\{
    Authorization as AuthorizationForm,
    AuthorizationRevocation as AuthorizationRevocationForm,
    Document as DocumentForm,
    Minutes as MinutesForm,
    ReorderDocument as ReorderDocumentForm,
    SearchDecision as SearchDecisionForm,
};
use Decision\Mapper\{
    Authorization as AuthorizationMapper,
    Decision as DecisionMapper,
    Meeting as MeetingMapper,
    MeetingDocument as MeetingDocumentMapper,
    MeetingMinutes as MeetingMinutesMapper,
    Member as MemberMapper,
};
use Decision\Model\{
    Authorization as AuthorizationModel,
    Enums\MembershipTypes,
    Meeting as MeetingModel,
    MeetingDocument as MeetingDocumentModel,
    MeetingMinutes as MeetingMinutesModel,
};
use Decision\Model\Enums\MeetingTypes;
use Doctrine\ORM\{
    Exception\ORMException,
    NonUniqueResultException,
    PersistentCollection,
};
use Exception;
use InvalidArgumentException;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\I18n\Translator;
use NumberFormatter;
use User\Permissions\NotAllowedException;

/**
 * Decision service.
 */
class Decision
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly FileStorageService $storageService,
        private readonly EmailService $emailService,
        private readonly MemberMapper $memberMapper,
        private readonly MeetingMapper $meetingMapper,
        private readonly MeetingDocumentMapper $meetingDocumentMapper,
        private readonly MeetingMinutesMapper $meetingMinutesMapper,
        private readonly DecisionMapper $decisionMapper,
        private readonly AuthorizationMapper $authorizationMapper,
        private readonly MinutesForm $minutesForm,
        private readonly DocumentForm $documentForm,
        private readonly ReorderDocumentForm $reorderDocumentForm,
        private readonly SearchDecisionForm $searchDecisionForm,
        private readonly AuthorizationForm $authorizationForm,
        private readonly AuthorizationRevocationForm $authorizationRevocationForm,
    ) {
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
     * @param int $limit The amount of meetings to retrieve
     * @param MeetingTypes $type Constraint on the type of the meeting
     *
     * @return array Of all meetings
     */
    public function getPastMeetings(
        int $limit,
        MeetingTypes $type,
    ): array {
        if (!$this->aclService->isAllowed('list_meetings', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list meetings.'));
        }

        return $this->meetingMapper->findPast($limit, $type);
    }

    /**
     * @param MeetingTypes $type
     *
     * @return array
     */
    public function getMeetingsByType(MeetingTypes $type): array
    {
        if (!$this->aclService->isAllowed('list_meetings', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list meetings.'));
        }

        return $this->meetingMapper->findByType($type);
    }

    /**
     * Get information about one meeting.
     *
     * @param MeetingTypes $type
     * @param int $number
     *
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    public function getMeeting(
        MeetingTypes $type,
        int $number,
    ): ?MeetingModel {
        if (!$this->aclService->isAllowed('view', 'meeting')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view meetings.'));
        }

        return $this->meetingMapper->findMeeting($type, $number);
    }

    /**
     * Returns the latest upcoming ALV or null if there is none.
     *
     * @return MeetingModel|null
     * @throws NonUniqueResultException
     */
    public function getLatestALV(): ?MeetingModel
    {
        return $this->meetingMapper->findLatestALV();
    }

    /**
     * Returns the closest upcoming meeting for members.
     *
     * @return array<array-key, MeetingModel>
     */
    public function getUpcomingAnnouncedMeetings(): array
    {
        return $this->meetingMapper->findUpcomingAnnouncedMeetings();
    }

    /**
     * Get meeting documents corresponding to a certain id.
     *
     * @param int $id
     *
     * @return MeetingDocumentModel|null
     */
    public function getMeetingDocument(int $id): ?MeetingDocumentModel
    {
        return $this->meetingDocumentMapper->find($id);
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
     * Returns a download for meeting minutes.
     *
     * @param MeetingModel $meeting
     *
     * @return Stream|null
     */
    public function getMeetingMinutesDownload(MeetingModel $meeting): ?Stream
    {
        if (!$this->aclService->isAllowed('view_minutes', 'meeting')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view meeting minutes.'));
        }

        if (is_null($meeting->getMinutes())) {
            return null;
        }

        $path = $meeting->getMinutes()->getPath();
        $fileName = $meeting->getType()->value . '-' . $meeting->getNumber() . '.pdf';

        return $this->storageService->downloadFile($path, $fileName);
    }

    /**
     * Upload meeting minutes.
     *
     * @param array $data
     *
     * @return bool If uploading was a success
     * @throws Exception
     */
    public function uploadMinutes(array $data): bool
    {
        $parts = explode('/', $data['meeting']);
        $meeting = $this->getMeeting(MeetingTypes::from($parts[0]), intval($parts[1]));
        $path = $this->storageService->storeUploadedFile($data['upload']);

        $meetingMinutes = $meeting->getMinutes();
        if (is_null($meetingMinutes)) {
            $meetingMinutes = new MeetingMinutesModel();
            $meetingMinutes->setMeeting($meeting);
        }

        $meetingMinutes->setPath($path);

        $this->meetingMinutesMapper->persist($meetingMinutes);

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
        $meeting = $this->getMeeting(MeetingTypes::from($meeting[0]), intval($meeting[1]));

        $document = new MeetingDocumentModel();
        $document->setPath($path);
        $document->setName($data['name']);
        $document->setMeeting($meeting);

        // Determine document's position in ordering
        $maxPosition = $this->meetingMapper->findMaxDocumentPosition($meeting);
        $position = is_null($maxPosition) ? 0 : ++$maxPosition; // NULL if meeting doesn't have documents yet

        $document->setDisplayPosition($position);

        $this->meetingDocumentMapper->persist($document);

        return true;
    }

    /**
     * @param MeetingDocumentModel $meetingDocument
     *
     * @throws ORMException
     */
    public function deleteDocument(MeetingDocumentModel $meetingDocument): void
    {
        if (!$this->aclService->isAllowed('delete_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete meeting documents.')
            );
        }

        // TODO: The actual file is never deleted.
        $this->meetingDocumentMapper->remove($meetingDocument);
    }

    /**
     * @param MeetingDocumentModel $meetingDocument
     * @param array $data
     *
     * @throws ORMException
     */
    public function renameDocument(
        MeetingDocumentModel $meetingDocument,
        array $data,
    ): void {
        $meetingDocument->setName($data['name']);
        $this->meetingDocumentMapper->persist($meetingDocument);
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
        $documents = $this->meetingDocumentMapper
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

            $this->meetingDocumentMapper->persist($document);
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

        return [
            'valid' => $this->authorizationMapper->findAllByType($meetingNumber),
            'revoked' => $this->authorizationMapper->findAllByType($meetingNumber, true),
        ];
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

        return $this->authorizationMapper->findUserAuthorization(
            $meeting->getNumber(),
            $this->aclService->getUserIdentityOrThrowException()->getMember(),
        );
    }

    /**
     * @param array $data
     *
     * @return AuthorizationModel|null
     * @throws ORMException
     */
    public function createAuthorization(array $data): ?AuthorizationModel
    {
        $authorizer = $this->aclService->getUserIdentityOrThrowException()->getMember();
        $recipient = $this->memberMapper->findByLidnr(intval($data['recipient']));

        if (
            null === $recipient
            || $recipient->getLidnr() === $authorizer->getLidnr()
            || $recipient->getDeleted()
            || $recipient->isExpired()
            || MembershipTypes::Graduate === $recipient->getType()
        ) {
            return null;
        }

        $meeting = $this->getLatestALV();
        if (null === $meeting) {
            return null;
        }

        // You cannot authorize more than one person, actually return the existing authorization model to ensure the
        // form is not shown again on a refresh.
        if (null !== ($previousAuthorization = $this->getUserAuthorization($meeting))) {
            return $previousAuthorization;
        }

        $authorization = new AuthorizationModel();

        $authorization->setAuthorizer($authorizer);
        $authorization->setRecipient($recipient);
        $authorization->setMeetingNumber($meeting->getNumber());
        $authorization->setCreatedAt(new DateTime());
        $this->authorizationMapper->persist($authorization);

        $nf = new NumberFormatter('en_GB', NumberFormatter::ORDINAL);

        // Send an email to the recipient
        $this->emailService->sendEmailAsUserToUser(
            $recipient,
            'email/authorization-grantee',
            'GMM Authorization Granted',
            [
                'grantor' => $authorizer,
                'grantee' => $recipient,
                'meetingNumber' => $nf->format($meeting->getNumber()),
                'meetingDate' => $meeting->getDate()->format('l, F j, Y'),
            ],
            $authorizer,
        );

        // Send a confirmation email to the authorizing member
        $this->emailService->sendEmailAsUserToUser(
            $authorizer,
            'email/authorization-grantor',
            'GMM Authorization Granted',
            [
                'grantor' => $authorizer,
                'grantee' => $recipient,
                'meetingNumber' => $nf->format($meeting->getNumber()),
                'meetingDate' => $meeting->getDate()->format('l, F j, Y'),
            ],
            $recipient,
        );

        return $authorization;
    }

    public function revokeAuthorization(AuthorizationModel $authorization): void
    {
        $authorization->setRevokedAt(new DateTime());
        $this->authorizationMapper->persist($authorization);

        $nf = new NumberFormatter('en_GB', NumberFormatter::ORDINAL);

        $this->emailService->sendEmailAsUserToUser(
            $authorization->getRecipient(),
            'email/authorization-revoked',
            'GMM Authorization Revoked',
            [
                'grantor' => $authorization->getAuthorizer(),
                'grantee' => $authorization->getRecipient(),
                'meetingNumber' => $nf->format($authorization->getMeetingNumber()),
            ],
            $authorization->getAuthorizer(),
        );
    }

    /**
     * Get the meeting minutes form.
     *
     * @return MinutesForm
     */
    public function getMinutesForm(): MinutesForm
    {
        if (!$this->aclService->isAllowed('upload_minutes', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to upload meeting minutes.')
            );
        }

        return $this->minutesForm->setMeetings($this->meetingMapper->findAllMeetings());
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
            throw new NotAllowedException($this->translator->translate('You are not allowed authorize people.'));
        }

        return $this->authorizationForm;
    }

    /**
     * Get the Authorization revocation form.
     *
     * @return AuthorizationRevocationForm
     */
    public function getAuthorizationRevocationForm(): AuthorizationRevocationForm
    {
        if (!$this->aclService->isAllowed('revoke', 'authorization')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to revoke authorizations.'));
        }

        return $this->authorizationRevocationForm;
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
