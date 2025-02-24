<?php

declare(strict_types=1);

namespace Decision\Service;

use Application\Service\Email as EmailService;
use Application\Service\FileStorage as FileStorageService;
use DateTime;
use Decision\Form\Authorization as AuthorizationForm;
use Decision\Form\AuthorizationRevocation as AuthorizationRevocationForm;
use Decision\Form\Document as DocumentForm;
use Decision\Form\Minutes as MinutesForm;
use Decision\Form\SearchDecision as SearchDecisionForm;
use Decision\Mapper\Authorization as AuthorizationMapper;
use Decision\Mapper\Decision as DecisionMapper;
use Decision\Mapper\Meeting as MeetingMapper;
use Decision\Mapper\MeetingDocument as MeetingDocumentMapper;
use Decision\Mapper\MeetingMinutes as MeetingMinutesMapper;
use Decision\Mapper\Member as MemberMapper;
use Decision\Model\Authorization as AuthorizationModel;
use Decision\Model\Decision as DecisionModel;
use Decision\Model\Enums\MeetingTypes;
use Decision\Model\Enums\MembershipTypes;
use Decision\Model\Meeting as MeetingModel;
use Decision\Model\MeetingDocument as MeetingDocumentModel;
use Decision\Model\MeetingMinutes as MeetingMinutesModel;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Exception;
use InvalidArgumentException;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\I18n\Translator;
use NumberFormatter;
use User\Permissions\NotAllowedException;

use function array_flip;
use function array_search;
use function array_splice;
use function count;
use function explode;
use function intval;
use function pathinfo;

use const PATHINFO_EXTENSION;

/**
 * Decision service.
 *
 * @psalm-import-type MeetingArrayType from MeetingMapper as ImportedMeetingArrayType
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
        private readonly SearchDecisionForm $searchDecisionForm,
        private readonly AuthorizationForm $authorizationForm,
        private readonly AuthorizationRevocationForm $authorizationRevocationForm,
    ) {
    }

    /**
     * Get the translator.
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
     * @return ImportedMeetingArrayType
     */
    public function getMeetings(
        ?int $limit = null,
        ?MeetingTypes $type = null,
    ): array {
        if (!$this->aclService->isAllowed('list_meetings', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list meetings'));
        }

        return $this->meetingMapper->findAllMeetings($limit, $type);
    }

    /**
     * Get past meetings.
     *
     * @param int          $limit The amount of meetings to retrieve
     * @param MeetingTypes $type  Constraint on the type of the meeting
     *
     * @return MeetingModel[]
     */
    public function getPastMeetings(
        int $limit,
        MeetingTypes $type,
    ): array {
        if (!$this->aclService->isAllowed('list_meetings', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list meetings'));
        }

        return $this->meetingMapper->findPast($limit, $type);
    }

    /**
     * @return MeetingModel[]
     */
    public function getMeetingsByType(MeetingTypes $type): array
    {
        if (!$this->aclService->isAllowed('list_meetings', 'decision')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list meetings'));
        }

        return $this->meetingMapper->findByType($type);
    }

    /**
     * Get information about one meeting.
     *
     * @throws NonUniqueResultException
     */
    public function getMeeting(
        MeetingTypes $type,
        int $number,
    ): ?MeetingModel {
        if (!$this->aclService->isAllowed('view', 'meeting')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view meetings'));
        }

        return $this->meetingMapper->findMeeting($type, $number);
    }

    /**
     * Returns the latest upcoming ALV or null if there is none.
     *
     * @throws NonUniqueResultException
     */
    public function getLatestALV(): ?MeetingModel
    {
        return $this->meetingMapper->findLatestALV();
    }

    /**
     * Returns the closest upcoming meeting for members.
     *
     * @return MeetingModel[]
     */
    public function getUpcomingAnnouncedMeetings(): array
    {
        return $this->meetingMapper->findUpcomingAnnouncedMeetings();
    }

    /**
     * Get meeting documents corresponding to a certain id.
     */
    public function getMeetingDocument(int $id): ?MeetingDocumentModel
    {
        return $this->meetingDocumentMapper->find($id);
    }

    /**
     * Returns a download for a meeting document.
     */
    public function getMeetingDocumentDownload(MeetingDocumentModel $meetingDocument): ?Stream
    {
        if (!$this->aclService->isAllowed('view_documents', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view meeting documents.'),
            );
        }

        $path = $meetingDocument->getPath();
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fileName = $meetingDocument->getName() . '.' . $extension;

        return $this->storageService->downloadFile($path, $fileName);
    }

    /**
     * Returns a download for meeting minutes.
     */
    public function getMeetingMinutesDownload(MeetingModel $meeting): ?Stream
    {
        if (!$this->aclService->isAllowed('view_minutes', 'meeting')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view meeting minutes'));
        }

        if (null === $meeting->getMinutes()) {
            return null;
        }

        $path = $meeting->getMinutes()->getPath();
        $fileName = $meeting->getType()->value . '-' . $meeting->getNumber() . '.pdf';

        return $this->storageService->downloadFile($path, $fileName);
    }

    /**
     * Upload meeting minutes.
     *
     * @return bool If uploading was a success
     *
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function uploadMinutes(array $data): bool
    {
        $parts = explode('/', $data['meeting']);
        $meeting = $this->getMeeting(MeetingTypes::from($parts[0]), intval($parts[1]));
        $path = $this->storageService->storeUploadedFile($data['upload']);

        $meetingMinutes = $meeting->getMinutes();
        if (null === $meetingMinutes) {
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
     * @return bool If uploading was a success
     *
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
        $position = null === $maxPosition ? 0 : ++$maxPosition; // NULL if meeting doesn't have documents yet

        $document->setDisplayPosition($position);

        $this->meetingDocumentMapper->persist($document);

        return true;
    }

    /**
     * @throws ORMException
     */
    public function deleteDocument(MeetingDocumentModel $meetingDocument): void
    {
        if (!$this->aclService->isAllowed('delete_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete meeting documents.'),
            );
        }

        // TODO: The actual file is never deleted.
        $this->meetingDocumentMapper->remove($meetingDocument);
    }

    /**
     * @throws ORMException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
     * The basic flow is (1) retrieve documents, (2) set document to new position,
     * then (3) persist position of all documents related to a meeting.
     * Old documents don't have a position yet, so they are set to 0 by default.
     *
     * FUTURE: When documents have display positions, simplify the code by only
     * mutating two rows.
     *
     * @param int $id          Document ID
     * @param int $newPosition The new position for the document
     *
     * @throws NotAllowedException
     * @throws InvalidArgumentException If the document doesn't exist.
     */
    public function changePositionDocument(
        int $id,
        int $newPosition,
    ): void {
        $this->isAllowedOrFail(
            'upload_document',
            'meeting',
            'You are not allowed to modify meeting documents.',
        );

        // Documents are ordered because of @OrderBy annotation on the relation
        /** @var PersistentCollection $documents */
        $documents = $this->meetingDocumentMapper
            ->findDocumentOrFail($id)
            ->getMeeting()
            ->getDocuments();

        // Convert the collection to an array to manipulate
        /** @var int[] $ordering */
        $ordering = $documents->map(static function (MeetingDocumentModel $document) {
            return $document->getId();
        })->toArray();

        $oldPosition = array_search($id, $ordering);

        if ($newPosition === $oldPosition) {
            return;
        }

        // Validate the new position
        if (
            $newPosition < 0
            || $newPosition >= count($ordering)
        ) {
            throw new InvalidArgumentException('Invalid position');
        }

        // Remove the document from its old position and insert in new position.
        array_splice($ordering, $oldPosition, 1);
        array_splice($ordering, $newPosition, 0, [$id]);

        // Flip array to make getting position easier.
        $newPositions = array_flip($ordering);
        foreach ($documents as $document) {
            $position = $newPositions[$document->getId()];
            $document->setDisplayPosition($position);

            $this->meetingDocumentMapper->persist($document);
        }
    }

    /**
     * Search for decisions.
     *
     * @param array $data Search data
     *
     * @return DecisionModel[]
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function search(array $data): array
    {
        return $this->decisionMapper->search($data['query']);
    }

    /**
     * Retrieves all authorizations for the given meeting number.
     *
     * @return array{
     *     valid: AuthorizationModel[],
     *     revoked: AuthorizationModel[],
     * }
     */
    public function getAllAuthorizations(int $meetingNumber): array
    {
        if (!$this->aclService->isAllowed('view_all', 'authorization')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view all authorizations.'),
            );
        }

        return [
            'valid' => $this->authorizationMapper->findAllByType($meetingNumber),
            'revoked' => $this->authorizationMapper->findAllByType($meetingNumber, true),
        ];
    }

    /**
     * Gets the authorization of the current user for the given meeting.
     */
    public function getUserAuthorization(MeetingModel $meeting): ?AuthorizationModel
    {
        if (!$this->aclService->isAllowed('view_own', 'authorization')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view authorizations'));
        }

        return $this->authorizationMapper->findUserAuthorization(
            $meeting->getNumber(),
            $this->aclService->getUserIdentityOrThrowException()->getMember(),
        );
    }

    /**
     * @throws ORMException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
     */
    public function getMinutesForm(): MinutesForm
    {
        if (!$this->aclService->isAllowed('upload_minutes', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to upload meeting minutes.'),
            );
        }

        return $this->minutesForm->setMeetings($this->meetingMapper->findAllMeetings());
    }

    /**
     * Get the Document form.
     */
    public function getDocumentForm(): DocumentForm
    {
        if (!$this->aclService->isAllowed('upload_document', 'meeting')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to upload meeting documents.'),
            );
        }

        return $this->documentForm;
    }

    /**
     * Get the SearchDecision form.
     */
    public function getSearchDecisionForm(): SearchDecisionForm
    {
        return $this->searchDecisionForm;
    }

    /**
     * Get the Authorization form.
     */
    public function getAuthorizationForm(): AuthorizationForm
    {
        if (!$this->aclService->isAllowed('create', 'authorization')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed authorize people'));
        }

        return $this->authorizationForm;
    }

    /**
     * Get the Authorization revocation form.
     */
    public function getAuthorizationRevocationForm(): AuthorizationRevocationForm
    {
        if (!$this->aclService->isAllowed('revoke', 'authorization')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to revoke authorizations.'),
            );
        }

        return $this->authorizationRevocationForm;
    }

    /**
     * Returns whether the current role is allowed to view files.
     */
    public function isAllowedToBrowseFiles(): bool
    {
        return $this->aclService->isAllowed('browse', 'files');
    }

    /**
     * Checks the user's permission.
     *
     * @param string $errorMessage English error message
     *
     * @throws NotAllowedException If the user doesn't have permission.
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
