<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    ManyToOne,
};
use User\Model\User as UserModel;

/**
 * Signup model.
 */
#[Entity]
class UserSignup extends Signup
{
    /**
     * Who is subscribed. This association cannot be nonnullable, as this breaks {@link ExternalSignup}.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        name: "user_lidnr",
        referencedColumnName: "lidnr",
    )]
    protected UserModel $user;

    /**
     * Get the full name of the user whom signed up for the activity.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->getUser()->getMember()->getFullName();
    }

    /**
     * Get the user that is signed up.
     *
     * @return UserModel
     */
    public function getUser(): UserModel
    {
        return $this->user;
    }

    /**
     * Set the user for the activity signup.
     */
    public function setUser(UserModel $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the email address of the user whom signed up for the activity.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->getUser()->getMember()->getEmail();
    }
}
