<?php

declare(strict_types=1);

namespace App\Entity\Decision\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * The actions recorded in the meeting activity log, each rendering as a full feed sentence.
 */
enum MeetingActivityVerbs: string
{
    case PointCreated = 'point_created';
    case PointUpdated = 'point_updated';
    case PointDeleted = 'point_deleted';
    case PointsReordered = 'points_reordered';
    case DocumentUploaded = 'document_uploaded';
    case DocumentVersionUploaded = 'document_version_uploaded';
    case DocumentRenamed = 'document_renamed';
    case DocumentDeleted = 'document_deleted';
    case DocumentsReordered = 'documents_reordered';
    case MinutesUploaded = 'minutes_uploaded';
    case MinutesDeleted = 'minutes_deleted';
    case ReferenceSelected = 'reference_selected';
    case ReferenceDeselected = 'reference_deselected';
    case ReferencePinned = 'reference_pinned';
    case ReferenceCarriedOver = 'reference_carried_over';
    case ReferenceDocumentCreated = 'reference_document_created';
    case ReferenceDocumentRenamed = 'reference_document_renamed';
    case ReferenceDocumentDeleted = 'reference_document_deleted';
    case DetailsUpdated = 'details_updated';

    public function message(
        string $actor,
        string $subject,
    ): TranslatableMessage {
        $parameters = [
            '%actor%' => $actor,
            '%subject%' => $subject,
        ];

        return match ($this) {
            self::PointCreated => new TranslatableMessage(
                '%actor% added agenda point %subject%',
                $parameters,
            ),
            self::PointUpdated => new TranslatableMessage(
                '%actor% updated agenda point %subject%',
                $parameters,
            ),
            self::PointDeleted => new TranslatableMessage(
                '%actor% removed agenda point %subject%',
                $parameters,
            ),
            self::PointsReordered => new TranslatableMessage(
                '%actor% reordered the agenda',
                $parameters,
            ),
            self::DocumentUploaded => new TranslatableMessage(
                '%actor% uploaded %subject%',
                $parameters,
            ),
            self::DocumentVersionUploaded => new TranslatableMessage(
                '%actor% uploaded a new version of %subject%',
                $parameters,
            ),
            self::DocumentRenamed => new TranslatableMessage(
                '%actor% renamed a document to %subject%',
                $parameters,
            ),
            self::DocumentDeleted => new TranslatableMessage(
                '%actor% removed %subject%',
                $parameters,
            ),
            self::DocumentsReordered => new TranslatableMessage(
                '%actor% reordered documents',
                $parameters,
            ),
            self::MinutesUploaded => new TranslatableMessage(
                '%actor% uploaded the minutes (%subject%)',
                $parameters,
            ),
            self::MinutesDeleted => new TranslatableMessage(
                '%actor% removed the minutes',
                $parameters,
            ),
            self::ReferenceSelected => new TranslatableMessage(
                '%actor% added reference document %subject%',
                $parameters,
            ),
            self::ReferenceDeselected => new TranslatableMessage(
                '%actor% removed reference document %subject%',
                $parameters,
            ),
            self::ReferencePinned => new TranslatableMessage(
                '%actor% changed which version of %subject% is shown',
                $parameters,
            ),
            self::ReferenceCarriedOver => new TranslatableMessage(
                '%actor% copied the reference selection from %subject%',
                $parameters,
            ),
            self::ReferenceDocumentCreated => new TranslatableMessage(
                '%actor% added %subject% to the library',
                $parameters,
            ),
            self::ReferenceDocumentRenamed => new TranslatableMessage(
                '%actor% renamed a library document to %subject%',
                $parameters,
            ),
            self::ReferenceDocumentDeleted => new TranslatableMessage(
                '%actor% removed %subject% from the library',
                $parameters,
            ),
            self::DetailsUpdated => new TranslatableMessage(
                '%actor% updated the time and place',
                $parameters,
            ),
        };
    }
}
