<?php

declare(strict_types=1);

namespace User\Model;

use Company\Model\Company as CompanyModel;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Temporary {@link CompanyUser} model to facilitate registration and password resets.
 */
#[Entity]
class NewCompanyUser
{
    /**
     * The company.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $id;

    /**
     * The company's activation/reset code.
     */
    #[Column(type: 'string')]
    protected string $code;

    /**
     * Date and time at which the activation or password reset was requested.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $time = null;

    /**
     * The company for this company user.
     */
    #[OneToOne(targetEntity: CompanyModel::class)]
    #[JoinColumn(
        name: 'id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyModel $company;

    public function __construct(CompanyModel $company)
    {
        $this->id = $company->getId();
        $this->company = $company;
    }

    /**
     * Get the internal identifier for this company.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the company.
     */
    public function getCompany(): CompanyModel
    {
        return $this->company;
    }

    /**
     * Get the email address of the company's representative.
     */
    public function getEmail(): string
    {
        return $this->company->getRepresentativeEmail();
    }

    /**
     * Get the activation/reset code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set the activation/reset code.
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Get the activation/reset time.
     */
    public function getTime(): ?DateTime
    {
        return $this->time;
    }

    /**
     * Set the activation/reset time.
     */
    public function setTime(?DateTime $time): void
    {
        $this->time = $time;
    }
}
