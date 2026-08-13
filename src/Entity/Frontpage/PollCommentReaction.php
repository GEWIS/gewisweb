<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Frontpage\Enums\PollCommentReactionType;
use App\Repository\Frontpage\PollCommentReactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * One member's response to a poll comment. Who reacted is kept only so the member can take it back or change it, and to
 * hold them to one reaction per comment; the website shows nothing but the counts.
 *
 * The member is dropped when the poll's votes are anonymised, which leaves the count intact and the reaction anonymous.
 */
#[Entity(repositoryClass: PollCommentReactionRepository::class)]
#[HasLifecycleCallbacks]
#[UniqueConstraint(
    name: 'poll_comment_reaction_uniq',
    columns: [
        'comment_id',
        'member_lidnr',
    ],
)]
class PollCommentReaction
{
    use IdentifiableTrait;
    use TimestampableTrait;

    #[ManyToOne(
        targetEntity: PollComment::class,
        inversedBy: 'reactions',
    )]
    #[JoinColumn(
        name: 'comment_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private PollComment $comment;

    /**
     * The member who reacted, or null once the poll has been anonymised.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: 'member_lidnr',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?MemberModel $member = null;

    #[Column(
        type: Types::STRING,
        enumType: PollCommentReactionType::class,
    )]
    private PollCommentReactionType $type;

    public function getComment(): PollComment
    {
        return $this->comment;
    }

    public function setComment(PollComment $comment): void
    {
        $this->comment = $comment;
    }

    public function getMember(): ?MemberModel
    {
        return $this->member;
    }

    public function setMember(?MemberModel $member): void
    {
        $this->member = $member;
    }

    public function getType(): PollCommentReactionType
    {
        return $this->type;
    }

    public function setType(PollCommentReactionType $type): void
    {
        $this->type = $type;
    }
}
