<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\ActivityRevisionEdit;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\SignupOption;
use App\Entity\Application\Enums\RevisionStatus;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

use function array_keys;
use function in_array;
use function spl_object_id;

/**
 * Appends an {@see ActivityRevisionEdit} audit row for every member-driven in-place edit of an activity revision, so a
 * change can always be attributed to the member who made it. It runs inside the same flush, so the audit row commits
 * together with the edit. System flushes (fixtures, the stale-draft cron, an approval) leave `lastEditedBy` unset and
 * are skipped.
 *
 * A single edit can touch the revision's own columns, its localised texts, its sign-up lists (their fields and options)
 * and its label assignments; all of these are folded into one audit row per revision per flush, with the union of the
 * changed content fields (sign-up-list and label changes surface as the synthetic `signupLists` / `labels` markers).
 */
#[AsDoctrineListener(event: Events::onFlush)]
final readonly class RevisionAuditListener
{
    /**
     * Workflow/bookkeeping columns whose change is not, on its own, a content edit worth auditing.
     */
    private const array IGNORED_FIELDS = [
        'version',
        'status',
        'revisionNumber',
        'reviewedAt',
        'reviewer',
        'previousRevision',
        'createdAt',
        'updatedAt',
        'lastEditedBy',
        'lastEditedByCompanyUser',
    ];

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $metadata = $entityManager->getClassMetadata(ActivityRevisionEdit::class);

        // Accumulate the changed content-field set per affected revision (keyed by object id), unioning the four
        // sources below. A set (field => true) dedupes fields touched via more than one source in one flush.
        /** @var array<int, array{revision: ActivityRevision, fields: array<string, true>}> $perRevision */
        $perRevision = [];
        $record = static function (
            ActivityRevision $revision,
            string $field,
        ) use (&$perRevision): void {
            $key = spl_object_id($revision);
            $perRevision[$key] ??= [
                'revision' => $revision,
                'fields' => [],
            ];
            $perRevision[$key]['fields'][$field] = true;
        };

        // 1 + 2: the revision's own changed columns (minus bookkeeping) and its localised texts.
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof ActivityRevision) {
                continue;
            }

            foreach (
                $this->changedFields(
                    $unitOfWork,
                    $entity,
                ) as $field
            ) {
                $record($entity, $field);
            }
        }

        // 3: sign-up list / field / option inserts, updates and deletions all attribute to their owning revision.
        foreach (
            [
                ...$unitOfWork->getScheduledEntityInsertions(),
                ...$unitOfWork->getScheduledEntityUpdates(),
                ...$unitOfWork->getScheduledEntityDeletions(),
            ] as $entity
        ) {
            $revision = $this->owningRevisionOfSignupStructure($entity);
            if (null === $revision) {
                continue;
            }

            $record($revision, 'signupLists');
        }

        // 4: label many-to-many collection changes (add/remove) owned by a revision.
        foreach (
            [
                ...$unitOfWork->getScheduledCollectionUpdates(),
                ...$unitOfWork->getScheduledCollectionDeletions(),
            ] as $collection
        ) {
            $owner = $collection->getOwner();
            if (
                !$owner instanceof ActivityRevision
                || 'labels' !== $collection->getMapping()->fieldName
            ) {
                continue;
            }

            $record($owner, 'labels');
        }

        foreach ($perRevision as ['revision' => $revision, 'fields' => $fields]) {
            $editor = $revision->getLastEditedBy();
            if (null === $editor) {
                // Not a member-driven in-place edit (a fixture, the stale-draft cron or an approval): nothing to log.
                continue;
            }

            if (RevisionStatus::Draft !== $revision->getStatus()) {
                // In-place edits only ever happen on a Draft (the sole author-editable state). A later workflow flush
                // can still carry a stale `lastEditedBy` from the last edit; scoping to Draft keeps a future
                // audit-worthy field written during such a flush from appending a phantom edit row attributed to it.
                continue;
            }

            if ([] === $fields) {
                continue;
            }

            $edit = new ActivityRevisionEdit();
            $edit->setRevision($revision);
            $edit->setEditor($editor);
            $edit->setEditedAt(new DateTime());
            $edit->setChangedFields(array_keys($fields));

            $entityManager->persist($edit);
            $unitOfWork->computeChangeSet(
                $metadata,
                $edit,
            );
        }
    }

    /**
     * The owning activity revision of a scheduled sign-up-list structure entity (the list itself, one of its fields, or
     * an option of a field), or null when the entity is not part of a sign-up-list structure.
     */
    private function owningRevisionOfSignupStructure(object $entity): ?ActivityRevision
    {
        return match (true) {
            $entity instanceof SignupList => $entity->getRevision(),
            $entity instanceof SignupField => $entity->getSignupList()->getRevision(),
            $entity instanceof SignupOption => $entity->getField()->getSignupList()->getRevision(),
            default => null,
        };
    }

    /**
     * The content fields changed in this save: the revision's own changed columns (minus bookkeeping) plus the
     * localised texts that were edited.
     *
     * @return string[]
     */
    private function changedFields(
        UnitOfWork $unitOfWork,
        ActivityRevision $revision,
    ): array {
        $fields = [];

        foreach (array_keys($unitOfWork->getEntityChangeSet($revision)) as $field) {
            if (
                in_array(
                    $field,
                    self::IGNORED_FIELDS,
                    true,
                )
            ) {
                continue;
            }

            $fields[] = $field;
        }

        $texts = [
            'name' => $revision->getName(),
            'location' => $revision->getLocation(),
            'costs' => $revision->getCosts(),
            'description' => $revision->getDescription(),
        ];
        foreach ($texts as $field => $text) {
            if ([] === $unitOfWork->getEntityChangeSet($text)) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }
}
