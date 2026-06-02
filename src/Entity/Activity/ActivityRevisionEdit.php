<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\User\User as UserModel;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * One entry in the audit trail of in-place edits to an {@see ActivityRevision} draft: who saved it, when, and which of
 * its fields they changed. Appended automatically on every member-driven save by
 * {@see \App\EventListener\Activity\RevisionAuditListener}, so any change can be attributed to the member who made it.
 */
#[Entity]
class ActivityRevisionEdit
{
    use IdentifiableTrait;

    /**
     * The revision this edit was made to.
     */
    #[ManyToOne(
        targetEntity: ActivityRevision::class,
        inversedBy: 'editHistory',
    )]
    #[JoinColumn(nullable: false)]
    private ActivityRevision $revision;

    /**
     * The user (a member's account) who made the edit; activities are only ever edited by members.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private UserModel $editor;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $editedAt;

    /**
     * The names of the revision fields that changed in this save (e.g. ['organ', 'name', 'beginTime']).
     *
     * @var string[]
     */
    #[Column(type: Types::JSON)]
    private array $changedFields = [];

    public function getRevision(): ActivityRevision
    {
        return $this->revision;
    }

    public function setRevision(ActivityRevision $revision): void
    {
        $this->revision = $revision;
    }

    public function getEditor(): UserModel
    {
        return $this->editor;
    }

    public function setEditor(UserModel $editor): void
    {
        $this->editor = $editor;
    }

    public function getEditedAt(): DateTime
    {
        return $this->editedAt;
    }

    public function setEditedAt(DateTime $editedAt): void
    {
        $this->editedAt = $editedAt;
    }

    /**
     * @return string[]
     */
    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    /**
     * @param string[] $changedFields
     */
    public function setChangedFields(array $changedFields): void
    {
        $this->changedFields = $changedFields;
    }

    /**
     * A human-readable name for whoever made this edit.
     */
    public function getEditorDisplayName(): string
    {
        return $this->editor->getMember()->getFullName();
    }
}
