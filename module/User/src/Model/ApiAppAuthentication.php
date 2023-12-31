<?php

declare(strict_types=1);

namespace User\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Log when a member has authenticated for an external app.
 *
 * @psalm-type ApiAppAuthenticationGdprArrayType = array{
 *     id: int,
 *     app_id: string,
 *     time: string,
 * }
 */
#[Entity]
class ApiAppAuthentication
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
    protected User $user;

    /**
     * The application that got the authentication.
     */
    #[ManyToOne(targetEntity: ApiApp::class)]
    #[JoinColumn(
        name: 'app_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ApiApp $apiApp;

    /**
     * Time of authentication.
     */
    #[Column(type: 'datetime')]
    protected DateTime $time;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getApiApp(): ApiApp
    {
        return $this->apiApp;
    }

    public function setApiApp(ApiApp $apiApp): void
    {
        $this->apiApp = $apiApp;
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
     * @return ApiAppAuthenticationGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'app_id' => $this->getApiApp()->getAppId(),
            'time' => $this->getTime()->format(DateTimeInterface::ATOM),
        ];
    }
}
