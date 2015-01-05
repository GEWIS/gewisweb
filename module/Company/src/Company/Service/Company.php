<?php

namespace Company\Service;

//use Application\Service\AbstractService;
use Application\Service\AbstractAclService;

use Company\Model\Company as CompanyModel;
use Company\Mapper\Company as CompanyMapper;
/**
 * Company service.
 */
class Company extends AbstractACLService
{
    public function getCompanyList()
    {
        if($this->isAllowed('list')){

            return $this->getCompanyMapper()->findAll();
        }
        else{
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to see all the companies')
            );
        }
    }
    // Company list for admin interface
    public function getHiddenCompanyList(){
        if($this->isAllowed('listall')){

            return $this->getCompanyMapper()->findAll();
        }
        else{
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to see all the companies')
            );
        }
    }
        
    public function getCompaniesWithAsciiName($asciiName)
    {
        return $this->getCompanyMapper()->findCompaniesWithAsciiName($asciiName);
    }

    public function getEditableCompaniesWithAsciiName($asciiName)
    {
        return $this->getCompanyMapper()->findEditableCompaniesWithAsciiName($asciiName);
    }
    public function getJobsWithAsciiName($companyAsciiName,$jobAsciiName)
    {
        return $this->getJobMapper()->findJobWithAsciiName($companyAsciiName,$jobAsciiName);
    }
    public function getCompanyMapper()
    {
        return $this->sm->get('company_mapper_company');
    }

    public function getJobList()
    {
        return $this->getJobMapper()->findAll();
    }

    public function getActiveJobList()
    {
        $jl = $this->getJobList();
        $r = array();
        foreach($jl as $j) {
            if ($j->getActive()) {
                array_push($r, $j);
            }
        }
        return $r;
    }

    public function getJobMapper()
    {
        return $this->sm->get('company_mapper_job');
    }
    /**
     * Get the Acl.
     *
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('company_acl');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'company';
    }
}
