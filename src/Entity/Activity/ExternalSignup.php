<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Repository\Activity\ExternalSignupRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Override;

/**
 * ExternalSignup model.
 */
#[Entity(repositoryClass: ExternalSignupRepository::class)]
class ExternalSignup extends Signup
{
    /**
     * The full name of the external subscriber.
     */
    #[Column(type: Types::STRING)]
    private string $fullName;

    /**
     * The email address of the external subscriber.
     */
    #[Column(type: Types::STRING)]
    private string $email;

    /**
     * When the external confirmed their email address (or was added pre-confirmed by an organiser).
     *
     * `null` while the double opt-in is still pending. This is the moment the external became a real subscriber, so it
     * is their effective sign-up time wherever admission needs one (a member's is `createdAt`). Null if and only if a
     * pending verification token exists: {@see \App\Service\Activity\SignupManager::confirmExternalSignup()} is the
     * only path that removes a token without deleting the sign-up, and it stamps this field.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $verifiedAt = null;

    /**
     * Whether an organiser/board member entered this sign-up on the person's behalf. A manually added external never
     * saw the sign-up form, so they did not themselves accept the activity and alcohol policies (a self sign-up cannot
     * exist without that agreement).
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $addedManually = false;

    /**
     * Gets the full name of the user who signed up for the activity.
     */
    #[Override]
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Sets the full name of the user who signed up for the activity.
     */
    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    /**
     * Get the email address of the user who signed up for the activity.
     */
    #[Override]
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Sets the email address of the user who signed up for the activity.
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getVerifiedAt(): ?DateTime
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?DateTime $verifiedAt): void
    {
        $this->verifiedAt = $verifiedAt;
    }

    public function isAddedManually(): bool
    {
        return $this->addedManually;
    }

    public function setAddedManually(bool $addedManually): void
    {
        $this->addedManually = $addedManually;
    }
}
