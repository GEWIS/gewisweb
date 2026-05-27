<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Repository\Activity\ExternalSignupRepository;
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
}
