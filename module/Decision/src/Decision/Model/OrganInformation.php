<?php

namespace Decision\Model;


class OrganInformation
{
    /**
     * Organ information ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Decision\Model\Organ", inversedBy="organInformation")
     * @ORM\JoinColumn(name="organ_id",referencedColumnName="id")
     */
    protected $organ;

    /**
     * The email address of the organ if available.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $email;

    /**
     * The website of the organ if available.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $website;

    /**
     * A short description of the organ in dutch.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $shortDutchDescription;

    /**
     * A description of the organ in dutch.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $dutchDescription;

    /**
     * A short description of the organ in english.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $shortEnglishDescription;

    /**
     * A description of the organ in english.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $englishDescription;

    /**
     * Who was the last one to approve this information. If null then nobody approved it.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(referencedColumnName="lidnr", nullable=true)
     */
    protected $approver;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOrgan()
    {
        return $this->organ;
    }

    /**
     * @param string $organ
     */
    public function setOrgan($organ)
    {
        $this->organ = $organ;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * @return string
     */
    public function getShortDutchDescription()
    {
        return $this->shortDutchDescription;
    }

    /**
     * @param string $shortDutchDescription
     */
    public function setShortDutchDescription($shortDutchDescription)
    {
        $this->shortDutchDescription = $shortDutchDescription;
    }

    /**
     * @return string
     */
    public function getDutchDescription()
    {
        return $this->dutchDescription;
    }

    /**
     * @param string $dutchDescription
     */
    public function setDutchDescription($dutchDescription)
    {
        $this->dutchDescription = $dutchDescription;
    }

    /**
     * @return string
     */
    public function getShortEnglishDescription()
    {
        return $this->shortEnglishDescription;
    }

    /**
     * @param string $shortEnglishDescription
     */
    public function setShortEnglishDescription($shortEnglishDescription)
    {
        $this->shortEnglishDescription = $shortEnglishDescription;
    }

    /**
     * @return string
     */
    public function getEnglishDescription()
    {
        return $this->englishDescription;
    }

    /**
     * @param string $englishDescription
     */
    public function setEnglishDescription($englishDescription)
    {
        $this->englishDescription = $englishDescription;
    }

    /**
     * @return \User\Model\User
     */
    public function getApprover()
    {
        return $this->approver;
    }

    /**
     * @param \User\Model\User $approver
     */
    public function setApprover($approver)
    {
        $this->approver = $approver;
    }



}