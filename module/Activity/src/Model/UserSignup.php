<?php

declare(strict_types=1);

namespace Activity\Model;

use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    ManyToOne,
};

/**
 * Signup model.
 */
#[Entity]
class UserSignup extends Signup
{
    /**
     * Who is subscribed. This association cannot be nonnullable, as this breaks {@link ExternalSignup}.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: "user_lidnr",
        referencedColumnName: "lidnr",
    )]
    protected MemberModel $user;

    /**
     * Get the full name of the user whom signed up for the activity.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->getUser()->getFullName();
    }

    /**
     * Get the user that is signed up.
     *
     * @return MemberModel
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
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getUser()->getEmail();
    }
}
