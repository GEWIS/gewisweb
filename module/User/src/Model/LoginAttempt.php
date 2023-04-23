<?php

declare(strict_types=1);

namespace User\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
};
use User\Model\{
    CompanyUser as CompanyUserModel,
    User as UserModel,
};

/**
 * A failed login attempt.
 */
#[Entity]
class LoginAttempt
{
    use IdentifiableTrait;

    /**
     * The user for which the login was attempted.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        name: "user_id",
        referencedColumnName: "lidnr",
    )]
    protected ?UserModel $user = null;

    /**
     * The user for which the login was attempted.
     */
    #[ManyToOne(targetEntity: CompanyUserModel::class)]
    #[JoinColumn(
        name: "company_id",
        referencedColumnName: "id",
    )]
    protected ?CompanyUserModel $companyUser = null;

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
     * @return UserModel|null
     */
    public function getUser(): ?UserModel
    {
        return $this->user;
    }

    /**
     * @param UserModel|null $user
     */
    public function setUser(?UserModel $user): void
    {
        $this->user = $user;
    }

    /**
     * @return CompanyUserModel|null
     */
    public function getCompanyUser(): ?CompanyUserModel
    {
        return $this->companyUser;
    }

    /**
     * @param CompanyUserModel|null $company
     */
    public function setCompanyUser(?CompanyUserModel $company): void
    {
        $this->companyUser = $company;
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
