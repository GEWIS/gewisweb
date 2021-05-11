<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Model\companyAccount as companyAccountModel;

use Zend\Http\Client as HttpClient;

class companyAccount extends AbstractAclService
{


    public function getActiveVacancies(){
        return $this->getcompanyAccountMapper()->findactiveVacancies();
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'member';
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


    public function getcompanyAccountMapper()
    {
        return $this->sm->get('decision_mapper_companyAccount');
    }
}
