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
use User\Model\CompanyUser as CompanyUserModel;
use User\Model\User as UserModel;

/**
 * A failed login attempt.
 *
 * @psalm-type LoginAttemptGdprArrayType = array{
 *     id: int,
 *     time: string,
 *     ip: string,
 * }
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
        name: 'user_id',
        referencedColumnName: 'lidnr',
    )]
    protected ?UserModel $user = null;

    /**
     * The user for which the login was attempted.
     */
    #[ManyToOne(targetEntity: CompanyUserModel::class)]
    #[JoinColumn(
        name: 'company_id',
        referencedColumnName: 'id',
    )]
    protected ?CompanyUserModel $companyUser = null;

    /**
     * The ip from which the login was attempted.
     */
    #[Column(type: 'string')]
    protected string $ip;

    /**
     * Attempt timestamp.
     */
    #[Column(type: 'datetime')]
    protected DateTime $time;

    public function getUser(): ?UserModel
    {
        return $this->user;
    }

    public function setUser(?UserModel $user): void
    {
        $this->user = $user;
    }

    public function getCompanyUser(): ?CompanyUserModel
    {
        return $this->companyUser;
    }

    public function setCompanyUser(?CompanyUserModel $company): void
    {
        $this->companyUser = $company;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
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
     * @return LoginAttemptGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'time' => $this->getTime()->format(DateTimeInterface::ATOM),
            'ip' => $this->getIp(),
        ];
    }
}
