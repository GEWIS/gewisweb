<?php

namespace Company\Service;

//use Application\Service\AbstractService;
use Application\Service\AbstractAclService;

/**
 * Company service.
 */
class Company extends AbstractACLService
{
    public function getCompanyList()
    {
        $translator = $this->getTranslator();
        if ($this->isAllowed('list')) {
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
        if ($this->isAllowed('listall')) {
            return $this->getCompanyMapper()->findAll();
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to acces the admin interface')
            );
        }
    }

    public function savePacketWithData($packet,$data)
    {
        $packetForm = $this->getPacketForm();
        $packetForm->setData($data);
        if ($packetForm->isValid()){
            $packet->exchangeArray($data); 
            $this->savePacket();
        }
    }

    public function saveCompanyWithData($company,$data)
    {
        $companyForm = $this->getCompanyForm();
        $companyForm->setData($data);
        if ($companyForm->isValid()){
            $company->exchangeArray($data); 
            $this->saveCompany();
        }
    }

    public function saveJobWithData($job,$data)
    {
        $jobForm = $this->getJobForm();
        $jobForm->setData($data);
        if ($jobForm->isValid()){
            $job->exchangeArray($data); 
            $this->saveJob();
        }
    }

    public function saveJob()
    {
        $this->getJobMapper()->save();
    }

    public function saveCompany()
    {
        $this->getCompanyMapper()->save();
    }

    public function savePacket()
    {
        $this->getPacketMapper()->save();
    }

    public function insertCompanyWithData($data)
    {
        $companyForm = $this->getCompanyForm();
        $companyForm->setData($data);
        if ($companyForm->isValid()) {
            $company = $this->insertCompany($data['languages']);
            $company->exchangeArray($data);
            $this->saveCompany();
            return true;
        }
        return false;
    }
    public function insertCompany($languages)
    {
        if ($this->isAllowed('insert')) {
            return $this->getCompanyMapper()->insert($languages);
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a company')
            );
        }
    }

    public function insertPacketForCompanySlugNameWithData($companySlugName,$data)
    {
        $packetForm = $this->getPacketForm();
        $packetForm->setData($data);
        if ($packetForm->isValid()) {
            $packet = $this->insertPacketForCompanySlugName($companySlugName);
            $packet->exchangeArray($data);
            $this->savePacket();
            return true;
        }
        return false;
    }

    public function insertPacketForCompanySlugName($companySlugName)
    {
        if ($this->isAllowed('insert')) {
            $companies = $this->getEditableCompaniesWithSlugName($companySlugName);
            var_dump($companySlugName);
            $company = $companies[0];

            return $this->getPacketMapper()->insertPacketIntoCompany($company);
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a packet')
            );
        }
    }
    public function insertJobIntoPacketIDWithData($packetID,$data)
    {
        $jobForm = $this->getJobForm();
        $jobForm->setData($data);
        if ($jobForm->isValid()) {
            $job = $this->insertJobIntoPacketID($packetId);
            $job->exchangeArray($data);
            $this->saveCompany();
            return true;
        }
        return false;
    }
    public function insertJobIntoPacketID($packetID)
    {
        if ($this->isAllowed('insert')) {
            $packet = $this->getEditablePacket($packetID);
            $result = $this->getJobMapper()->insertIntoPacket($packet);

            return $result;
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a job')
            );
        }
    }

    public function deletePacket($packetID)
    {
        if ($this->isAllowed('delete')) {
            return $this->getPacketMapper()->delete($packetID);
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete packets')
            );
        }
    }
    public function deleteCompaniesWithSlug($slug)
    {
        if ($this->isAllowed('delete')) {
            return $this->getCompanyMapper()->deleteWithSlug($slug);
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete companies')
            );
        }
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
        if ($this->isAllowed('edit')) {
            $packet = $this->getPacketMapper()->findEditablePacket($packetID);

            return $packet;
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit packets')
            );
        }
    }

    public function getEditableCompaniesWithSlugName($slugName)
    {
        if ($this->isAllowed('edit')) {
            return $this->getCompanyMapper()->findEditableCompaniesWithSlugName($slugName, true);
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit companies')
            );
        }
    }

    public function getEditableJobsWithSlugName($companySlugName, $jobSlugName)
    {
        if ($this->isAllowed('edit')) {
            return $this->getJobMapper()->findJobWithSlugName($companySlugName, $jobSlugName);
        } else {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit jobs')
            );
        }
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
        $r = [];
        foreach ($jl as $j) {
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
