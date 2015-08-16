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
        if ($this->isAllowed('list')){

            return $this->getCompanyMapper()->findAll($translator->getLocale());
        } else {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed list the companies')
            );
        }
    }
    // Company list for admin interface
    public function getHiddenCompanyList() 
    {
        if ($this->isAllowed('listall')){

            return $this->getCompanyMapper()->findAll();
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to see all the companies')
            );
        }
    }
    
    public function saveCompany()
    {
        $this->getCompanyMapper()->save();
    }

    public function savePacket()
    {
        $this->getPacketMapper()->save();
    }

    public function insertCompany($languages)
    {
        return $this->getCompanyMapper()->insert($languages);
    }
        
    public function insertPacketForCompanySlugName($companySlugName)
    {
        $companies = $this->getEditableCompaniesWithSlugName($companySlugName);
        var_dump($companySlugName);
        $company = $companies[0];
        return $this->getPacketMapper()->insertPacketIntoCompany($company);
    }

    public function deletePacket($packetID)
    {
        return $this->getPacketMapper()->delete($packetID);
    }
    public function deleteCompaniesWithSlug($slug)
    {
        return $this->getCompanyMapper()->deleteWithSlug($slug);
    }

    public function insertJobIntoPacketID($packetID)
    {
        $packet = $this->getEditablePacket($packetID);
        $result = $this->getJobMapper()->insertIntoPacket($packet);
        return $result;
    }
    
    public function getJobsWithCompanySlugName($companySlugName)
    {
        $return = $this->getJobMapper()->findJobsWithCompanySlugName($companySlugName);
        return $return;
    }
    
    public function getCompaniesWithSlugName($slugName)
    {
        return $this->getCompanyMapper()->findCompaniesWithSlugName($slugName);
    }

    public function getEditablePacket($packetID)
    {
        return $this->getPacketMapper()->findEditablePacket($packetID);
    }

    public function getEditableCompaniesWithSlugName($slugName)
    {
        return $this->getCompanyMapper()->findEditableCompaniesWithSlugName($slugName, true);
    }
    
    public function getEditableJobsWithSlugName($slugName, $jobSlugName)
    {
        return $this->getJobMapper()->findJobWithSlugName($slugName, $jobSlugName);
    }
    
    public function getJobsWithSlugName($companySlugName, $jobSlugName)
    {
        return $this->getJobMapper()->findJobWithSlugName($companySlugName, $jobSlugName);
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
    public function getCompanyForm()
    {
        return $this->sm->get('company_admin_edit_company_form');
    }

    public function getPacketForm()
    {
        return $this->sm->get('company_admin_edit_packet_form');
    }
    
    public function getJobForm()
    {
        return $this->sm->get('company_admin_edit_job_form');
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

    public function getCompanyMapper()
    {
        return $this->sm->get('company_mapper_company');
    }

    public function getPacketMapper()
    {
        return $this->sm->get('company_mapper_packet');
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
