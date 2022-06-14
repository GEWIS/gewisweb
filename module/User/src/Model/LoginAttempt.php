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
 * A failed login attempt.
 */
#[Entity]
class LoginAttempt
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * The user for which the login was attempted.
     */
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: "user_id",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected User $user;

    /**
     * The ip from which the login was attempted.
     */
    #[Column(type: "string")]
    protected string $ip;

    /**
     * Attempt timestamp.
     */
    #[Column(type: "datetime")]
    protected DateTime $time;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return DateTime
     */
    public function getTime(): DateTime
    {
        return $this->time;
    }

    /**
     * @param DateTime $time
     */
    public function setTime(DateTime $time): void
    {
        $this->time = $time;
    }
}
