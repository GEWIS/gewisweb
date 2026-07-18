<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Traits\IdentifiableTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Log when a member has authenticated for an external app.
 *
 * @psalm-type ExternalAppAuthenticationGdprArrayType = array{
 *     id: int,
 *     app_id: string,
 *     time: string,
 * }
 */
#[Entity]
class ExternalAppAuthentication
{
    use IdentifiableTrait;

    /**
     * The user who was authenticated.
     */
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'user_id',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private User $user;

    /**
     * The application that got the authentication.
     */
    #[ManyToOne(targetEntity: ExternalApp::class)]
    #[JoinColumn(
        name: 'app_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ExternalApp $externalApp;

    /**
     * Time of authentication.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $time;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getExternalApp(): ExternalApp
    {
        return $this->externalApp;
    }

    public function setExternalApp(ExternalApp $externalApp): void
    {
        $this->externalApp = $externalApp;
    }

    public function getTime(): DateTime
    {
        return $this->time;
    }

    public function setTime(DateTime $time): void
    {
        $this->time = $time;
    }

    /**
     * @return ExternalAppAuthenticationGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'app_id' => $this->getExternalApp()->getAppId(),
            'time' => $this->getTime()->format(DateTimeInterface::ATOM),
        ];
    }
}
