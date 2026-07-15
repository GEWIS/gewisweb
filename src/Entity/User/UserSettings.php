<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\User\Enums\PhotoVisibility;
use App\Repository\User\UserSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Per-member, app-owned settings and privacy preferences.
 *
 * These live on the `User` side (keyed by `lidnr`, via a derived/shared identity to {@see User}) and never on
 * `Member`, because the `Member` table is synced read-only from GEWISDB. A member has at most one row; a missing row
 * means "all defaults" (see the null-safe accessors on {@see User}), so rows are only created the first time a member
 * touches their settings.
 *
 * @psalm-type UserSettingsGdprArrayType = array{
 *     disableCosmetics: bool,
 *     photoTaggingOptOut: bool,
 *     photoVisibility: string,
 *     hideYearOfBirth: bool,
 *     hideBirthdayOnFrontpage: bool,
 * }
 */
#[Entity(repositoryClass: UserSettingsRepository::class)]
class UserSettings
{
    /**
     * The member these settings belong to. Its `lidnr` is also this entity's primary key (derived identity), mirroring
     * how {@see User} keys itself off {@see \App\Entity\Decision\Member}.
     */
    #[Id]
    #[OneToOne(
        targetEntity: User::class,
        inversedBy: 'settings',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private User $user;

    /**
     * Whether to hide the festive cosmetics (balloons, snow, fireworks) for this member.
     */
    #[Column(
        type: Types::BOOLEAN,
        options: ['default' => false],
    )]
    private bool $disableCosmetics = false;

    /**
     * Whether this member has opted out of being tagged in photos.
     */
    #[Column(
        type: Types::BOOLEAN,
        options: ['default' => false],
    )]
    private bool $photoTaggingOptOut = false;

    /**
     * How much of this member's tagged-photo collection is hidden from other members on their photo page.
     */
    #[Column(
        type: Types::STRING,
        enumType: PhotoVisibility::class,
        options: ['default' => PhotoVisibility::HideNone->value],
    )]
    private PhotoVisibility $photoVisibility = PhotoVisibility::HideNone;

    /**
     * Whether this member's year of birth (and thus age) is hidden from other members. Reciprocal: a member who hides
     * their own year of birth also stops seeing everyone else's.
     */
    #[Column(
        type: Types::BOOLEAN,
        options: ['default' => false],
    )]
    private bool $hideYearOfBirth = false;

    /**
     * Whether this member is excluded from the birthday panel on the home page.
     */
    #[Column(
        type: Types::BOOLEAN,
        options: ['default' => false],
    )]
    private bool $hideBirthdayOnFrontpage = false;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDisableCosmetics(): bool
    {
        return $this->disableCosmetics;
    }

    public function setDisableCosmetics(bool $disableCosmetics): void
    {
        $this->disableCosmetics = $disableCosmetics;
    }

    public function getPhotoTaggingOptOut(): bool
    {
        return $this->photoTaggingOptOut;
    }

    public function setPhotoTaggingOptOut(bool $photoTaggingOptOut): void
    {
        $this->photoTaggingOptOut = $photoTaggingOptOut;
    }

    public function getPhotoVisibility(): PhotoVisibility
    {
        return $this->photoVisibility;
    }

    public function setPhotoVisibility(PhotoVisibility $photoVisibility): void
    {
        $this->photoVisibility = $photoVisibility;
    }

    public function getHideYearOfBirth(): bool
    {
        return $this->hideYearOfBirth;
    }

    public function setHideYearOfBirth(bool $hideYearOfBirth): void
    {
        $this->hideYearOfBirth = $hideYearOfBirth;
    }

    public function getHideBirthdayOnFrontpage(): bool
    {
        return $this->hideBirthdayOnFrontpage;
    }

    public function setHideBirthdayOnFrontpage(bool $hideBirthdayOnFrontpage): void
    {
        $this->hideBirthdayOnFrontpage = $hideBirthdayOnFrontpage;
    }

    /**
     * @return UserSettingsGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'disableCosmetics' => $this->disableCosmetics,
            'photoTaggingOptOut' => $this->photoTaggingOptOut,
            'photoVisibility' => $this->photoVisibility->value,
            'hideYearOfBirth' => $this->hideYearOfBirth,
            'hideBirthdayOnFrontpage' => $this->hideBirthdayOnFrontpage,
        ];
    }
}
