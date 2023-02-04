<?php

namespace User\Model;

use Application\Model\IdentityInterface;
use Company\Model\Company as CompanyModel;
use Doctrine\ORM\Mapping\{Column,
    Entity,
    Id,
    JoinColumn,
    OneToOne,
};
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
    #[Column(type: "integer")]
    protected int $id;

    /**
     * The company's password.
     */
    #[Column(type: "string")]
    protected string $password;

    /**
     * The company for this company user.
     */
    #[OneToOne(
        targetEntity: CompanyModel::class,
        fetch: "EAGER",
    )]
    #[JoinColumn(
        name: "id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected CompanyModel $company;

    // phpcs:ignore Gewis.General.RequireConstructorPromotion -- not possible
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
     *
     * @return CompanyModel
     */
    public function getCompany(): CompanyModel
    {
        return $this->company;
    }

    /**
     * Get the email address of the company's representative.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->company->getRepresentativeEmail();
    }

    /**
     * Get the password hash.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set the password hash.
     *
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getRoleId(): string
    {
        return 'company';
    }

    /**
     * Get the user's resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'company_user';
    }
}
