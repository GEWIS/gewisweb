<?php

namespace User\Model;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};

/**
 * Log when a member has authenticated for an external app.
 */
#[Entity]
class ApiAppAuthentication
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * The user who was authenticated.
     */
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: "user_id",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected User $user;

    /**
     * The application that got the authentication.
     */
    #[ManyToOne(targetEntity: ApiApp::class)]
    #[JoinColumn(
        name: "app_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected ApiApp $apiApp;

    /**
     * Time of authentication.
     */
    #[Column(type: "datetime")]
    protected DateTime $time;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

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
}
