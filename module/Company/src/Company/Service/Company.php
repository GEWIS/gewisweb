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
        $translator = $this->getTranslator();
        if($this->isAllowed('list')){

            return $this->getCompanyMapper()->findAll($translator->getLocale());
        }
        else{
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed list the companies')
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
    public function saveCompany(){
        $this->getCompanyMapper()->save();
    }

    public function insertCompany(){
        return $this->getCompanyMapper()->insert();
    }
        
    public function insertJobForCompanyAsciiName($asciiCompanyName){
        $company = $this->getEditableCompaniesWithAsciiName($asciiCompanyName)[0];

        $result = $this->getJobMapper()->insertIntoCompany($company);


        return $result;
    }
    public function getJobsWithCompanyAsciiName($companyAsciiName)
    {
        $return =  $this->getJobMapper()->findJobsWithCompanyAsciiName($companyAsciiName);

        return $return;
    }
    public function getCompaniesWithAsciiName($asciiName)
    {
        return $this->getCompanyMapper()->findCompaniesWithAsciiName($asciiName);
    }

    public function getEditableCompaniesWithAsciiName($asciiName)
    {
        return $this->getCompanyMapper()->findEditableCompaniesWithAsciiName($asciiName, true);
    }
    public function getEditableJobsWithAsciiName($asciiName, $jobAsciiName)
    {
        return $this->getJobMapper()->findJobWithAsciiName($asciiName, $jobAsciiName);
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
    /**
     * Get the Company Edit form.
     *
     * @return Company Edit form
     */
    public function getCompanyForm(){
        return $this->sm->get('company_admin_edit_company_form');
    }

    public function getJobForm(){
        return $this->sm->get('company_admin_edit_job_form');
    }
    public function getActiveJobList()
    {
        $jl = $this->getJobList();
        $r = [];
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
