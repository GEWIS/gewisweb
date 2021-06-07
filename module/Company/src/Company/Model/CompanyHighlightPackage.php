<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyHighlightPackage model.
 *
 * @ORM\Entity
 */
class CompanyHighlightPackage extends CompanyPackage //implements RoleInterface, ResourceInterface
{
    /**
     * The id of the highlighted vacancy.
     *
     * @ORM\OneToOne(targetEntity="\Company\Model\Job", inversedBy="packages")
     */
    protected $vacancy;

    /**
     * @return mixed
     */
    public function getVacancy()
    {
        return $this->vacancy;
    }

    /**
     * @param mixed $vacancy
     */
    public function setVacancy($vacancy)
    {
        $this->vacancy = $vacancy;
    }



    public function getType()
    {
        return "highlight";
    }

    public function exchangeArray($data)
    {
        parent::exchangeArray($data);
        $this->setVacancy((isset($data['vacancy_id'])) ? $data['vacancy_id'] : $this->getVacancy());
    }
}
