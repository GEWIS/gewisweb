<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;


class Acl extends AbstractHelper
{

    /**
     * Acl
     *
     * @var \Zend\Permissions\Acl\Acl
     */
    protected $acl;

    /**
     * Get the current user's role.
     *
     * @return User|string
     */
    protected $role;
    
    /**
     * Check if a operation is allowed for the current role.
     *
     * @param string $operation Operation to be checked.
     * @param string|ResourceInterface $resource Resource to be checked
     *
     * @return boolean
     */
    public function __invoke($operation, $resource)
    {
        return $this->getAcl()->isAllowed(
            $this->getRole(),
            $resource,
            $operation
        );
    }

    /**
     * Get the Acl.
     *
     * @return string
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Set the Acl.
     *
     * @param \Zend\Permissions\Acl\Acl $acl
     */
    public function setAcl($acl)
    {
        $this->acl = $acl;
    }

    /**
     * Get the current user's role.
     *
     * @return User|string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set the current user's role.
     *
     * @param User|string
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * Get the authentication service.
     *
     * @return ExamService
     */
    public function getExamService()
    {
        return $this->examService;
    }
}
