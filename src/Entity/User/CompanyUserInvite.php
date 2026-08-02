<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\SelectorTokenTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Career\Company as CompanyModel;
use App\Repository\User\CompanyUserInviteRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * A standing offer for somebody to represent a company in the careers portal. No account exists until the offer is
 * accepted, because an account needs a password and only the person behind the address can choose one.
 *
 * The link carries `selector.verifier` and only the hash of the verifier is stored, as with a password reset. It lasts
 * far longer than one, though: it is not a way back into an account somebody already has, so the risk of a link sitting
 * in a mailbox is different, and a week is what it takes for an invitation to survive a holiday.
 */
#[Entity(repositoryClass: CompanyUserInviteRepository::class)]
#[HasLifecycleCallbacks]
#[Index(
    columns: ['selector'],
    name: 'IDX_company_user_invite_selector',
)]
#[UniqueConstraint(
    name: 'company_user_invite_email_uniq',
    columns: ['email'],
)]
class CompanyUserInvite
{
    use IdentifiableTrait;
    use SelectorTokenTrait;
    use TimestampableTrait;

    /**
     * Who was invited, and the address the account will sign in with once they accept.
     */
    #[Column(type: Types::STRING)]
    private string $email;

    #[Column(type: Types::STRING)]
    private string $name;

    #[ManyToOne(targetEntity: CompanyModel::class)]
    #[JoinColumn(
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private CompanyModel $company;

    /**
     * The board or C4 member who sent the invitation, or null once their account is gone.
     */
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'invitedBy',
        referencedColumnName: 'lidnr',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?User $invitedBy = null;

    public function __construct(
        CompanyModel $company,
        string $email,
        string $name,
        ?User $invitedBy,
        string $selector,
        string $hashedToken,
        DateTimeImmutable $expiresAt,
    ) {
        $this->company = $company;
        $this->email = $email;
        $this->name = $name;
        $this->invitedBy = $invitedBy;
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;
        $this->expiresAt = $expiresAt;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCompany(): CompanyModel
    {
        return $this->company;
    }

    public function getInvitedBy(): ?User
    {
        return $this->invitedBy;
    }

    /**
     * Reissuing keeps the row, so an invitation that was resent does not turn into a second pending invitation for the
     * same address.
     */
    public function reissue(
        string $selector,
        string $hashedToken,
        DateTimeImmutable $expiresAt,
    ): void {
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;
        $this->expiresAt = $expiresAt;
    }
}
