<?php

declare(strict_types=1);

namespace User\Model;

use Application\Model\IdentityInterface;
use Company\Model\Company as CompanyModel;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use User\Model\NewCompanyUser as NewCompanyUserModel;

/**
 * CompanyUser model.
 */
#[Entity]
class CompanyUser implements IdentityInterface
{
    /**
     * The internal identifier for this company.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $id;

    /**
     * The company's password.
     */
    #[Column(type: 'string')]
    protected string $password;

    /**
     * The company for this company user.
     */
    #[OneToOne(
        targetEntity: CompanyModel::class,
        fetch: 'EAGER',
    )]
    #[JoinColumn(
        name: 'id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyModel $company;

    /**
     * Timestamp when the password was last changed.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $passwordChangedOn = null;

    public function __construct(NewCompanyUserModel $newCompanyUser)
    {
        $this->id = $newCompanyUser->getId();
        $this->company = $newCompanyUser->getCompany();
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
     * Get the password hash.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set the password hash.
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPasswordChangedOn(): ?DateTime
    {
        return $this->passwordChangedOn;
    }

    public function setPasswordChangedOn(DateTime $passwordChangedOn): void
    {
        $this->passwordChangedOn = $passwordChangedOn;
    }

    public function getRoleId(): string
    {
        return 'company';
    }

    /**
     * Get the user's resource ID.
     */
    public function getResourceId(): string
    {
        return 'company_user';
    }
}
