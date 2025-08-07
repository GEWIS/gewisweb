<?php

declare(strict_types=1);

namespace Decision\Model;

use Decision\Model\MailingList as MailingListModel;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Mailing List Member model (partial)
 *
 * To allow having additional properties in the many-to-many association between {@see MailingList}s and {@see Member}s
 * we use this class as a connector.
 *
 * Report assumes a full sync has happened (e.g. toBeDeleted entires don't exist)
 *
 * @psalm-import-type MailingListGdprArrayType from MailingListModel as ImportedMailingListGdprArrayType
 * @psalm-type MailingListMemberGdprArrayType = array{
 *     list: ImportedMailingListGdprArrayType,
 *     email: string,
 * }
 */
#[Entity]
#[UniqueConstraint(
    name: 'mailinglistmember_unique_idx',
    columns: ['mailingList', 'member', 'email'],
)]
class MailingListMember
{
    /**
     * Mailing list.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: MailingList::class,
        inversedBy: 'mailingListMemberships',
    )]
    #[JoinColumn(
        name: 'mailingList',
        referencedColumnName: 'name',
    )]
    private MailingList $mailingList;

    /**
     * Member.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'mailingListMemberships',
    )]
    #[JoinColumn(
        name: 'member',
        referencedColumnName: 'lidnr',
    )]
    private Member $member;

    /**
     * Email address on the list
     */
    #[Id]
    #[Column(
        type: 'string',
        nullable: false,
    )]
    private string $email;

    public function __construct()
    {
    }

    /**
     * Get the mailing list.
     */
    public function getMailingList(): MailingList
    {
        return $this->mailingList;
    }

    /**
     * Set the mailing list.
     */
    public function setMailingList(MailingList $mailingList): void
    {
        $this->mailingList = $mailingList;
    }

    /**
     * Get the member.
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the email address of this subscription
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set the email address of this subscription
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return MailingListMemberGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'list' => $this->mailingList->toGdprArray(),
            'email' => $this->getEmail(),
        ];
    }
}
