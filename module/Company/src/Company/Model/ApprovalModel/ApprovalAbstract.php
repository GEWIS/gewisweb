<?php
namespace Company\Model\ApprovalModel;
use Doctrine\ORM\Mapping as ORM;

interface ApprovalAbstract
{
    /**
     * Get the approval's id.
     *
     * @return int
     */
    public function getId();

    /**
     * Get the approval's company.
     *
     * @return Company
     */
    public function getCompany();

    /**
     * Get the approval's approval status.
     *
     * @return boolean
     */
    public function getRejected();

}
