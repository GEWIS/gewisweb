<?php


namespace Decision\Service;


use Application\Service\AbstractAclService;

class CompanyAccount extends AbstractAclService
{

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'companyaccount';
    }

    /**
     * Get the Acl.
     *
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('decision_acl');
    }

}
