<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\ActivityRevisionEdit;
use App\Entity\Application\Enums\RevisionStatus;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

use function array_keys;
use function in_array;

/**
 * Appends an {@see ActivityRevisionEdit} audit row for every member-driven in-place edit of an activity revision, so a
 * change can always be attributed to the member who made it. It runs inside the same flush, so the audit row commits
 * together with the edit. System flushes (fixtures, the stale-draft cron, an approval) leave `lastEditedBy` unset and
 * are skipped.
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

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof ActivityRevision) {
                continue;
            }

            $editor = $entity->getLastEditedBy();
            if (null === $editor) {
                // Not a member-driven in-place edit (a fixture, the stale-draft cron or an approval): nothing to log.
                continue;
            }

            if (RevisionStatus::Draft !== $entity->getStatus()) {
                // In-place edits only ever happen on a Draft (the sole author-editable state). A later workflow flush
                // can still carry a stale `lastEditedBy` from the last edit; scoping to Draft keeps a future
                // audit-worthy field written during such a flush from appending a phantom edit row attributed to it.
                continue;
            }

            $changedFields = $this->changedFields(
                $unitOfWork,
                $entity,
            );
            if ([] === $changedFields) {
                continue;
            }

            $edit = new ActivityRevisionEdit();
            $edit->setRevision($entity);
            $edit->setEditor($editor);
            $edit->setEditedAt(new DateTime());
            $edit->setChangedFields($changedFields);

            $entityManager->persist($edit);
            $unitOfWork->computeChangeSet(
                $metadata,
                $edit,
            );
        }
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
