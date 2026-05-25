<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Decision\Member as MemberModel;
use App\Repository\Activity\UserSignupRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Override;

/**
 * Signup model.
 */
#[Entity(repositoryClass: UserSignupRepository::class)]
class UserSignup extends Signup
{
    /**
     * Who is subscribed. This association cannot be nonnullable, as this breaks {@link ExternalSignup}.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: 'user_lidnr',
        referencedColumnName: 'lidnr',
    )]
    private MemberModel $user;

    /**
     * Get the full name of the user whom signed up for the activity.
     */
    #[Override]
    public function getFullName(): string
    {
        return $this->getUser()->getFullName();
    }

    /**
     * Get the user that is signed up.
     */
    public function getUser(): MemberModel
    {
        return $this->user;
    }

    /**
     * Set the user for the activity signup.
     */
    public function setUser(MemberModel $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the email address of the user whom signed up for the activity.
     */
    #[Override]
    public function getEmail(): ?string
    {
        return $this->getUser()->getEmail();
    }
}
