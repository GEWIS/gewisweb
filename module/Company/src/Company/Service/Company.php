<?php

namespace Company\Service;

//use Application\Service\AbstractService;
use Application\Service\AbstractAclService;

/**
 * Company service.
 */
class Company extends AbstractACLService
{
    /**
     * Returns an list of all companies (excluding hidden companies
     *
     */
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
    /**
     * Returns a list of all companies (including hidden companies)
     *
     */
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

    /**
     * Checks if the data is valid, and if it is saves the packet
     *
     * @param mixed $packet
     * @param mixed $data
     */
    public function savePacketWithData($packet,$data)
    {
        $packetForm = $this->getPacketForm();
        $packetForm->setData($data);
        if ($packetForm->isValid()){
            $packet->exchangeArray($data); 
            $this->savePacket();
        }
    }

    /**
     * Checks if the data is valid, and if it is, saves the Company
     *
     * @param mixed $company
     * @param mixed $data
     */
    public function saveCompanyWithData($company,$data)
    {
        $companyForm = $this->getCompanyForm();
        $companyForm->setData($data);
        if ($companyForm->isValid()){
            $company->exchangeArray($data); 
            $this->saveCompany();
        }
    }

    /**
     * Checks if the data is valid, and if it is, saves the Job
     *
     * @param mixed $job
     * @param mixed $data
     */
    public function saveJobWithData($job,$data)
    {
        $jobForm = $this->getJobForm();
        $jobForm->setData($data);
        if ($jobForm->isValid()){
            $job->exchangeArray($data); 
            $this->saveJob();
        }
    }

    /**
     * Saves all modified jobs
     *
     */
    public function saveJob()
    {
        $this->getJobMapper()->save();
    }

    /**
     * Saves all modified companies
     *
     */
    public function saveCompany()
    {
        $this->getCompanyMapper()->save();
    }

    /**
     * Saves all modified packets
     *
     */
    public function savePacket()
    {
        $this->getPacketMapper()->save();
    }

    /**
     * Checks if the data is valid, and if it is, inserts the company, and sets 
     * all data
     *
     * @param mixed $data
     */
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
    /**
     * Inserts the company and initializes translations for the given languages
     *
     * @param mixed $languages
     */
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

    /**
     * Checks if the data is valid, and if it is, inserts the packet, and assigns it to the given company
     *
     * @param mixed $companySlugName
     * @param mixed $data
     */
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

    /**
     * Inserts a packet and assigns it to the given company
     *
     * @param mixed $companySlugName
     */
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
    /**
     * Checks if the data is valid, and if it is, assigns a job, and bind it to 
     * the given packet
     *
     * @param mixed $packetID
     * @param mixed $data
     */
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
    /**
     * Inserts a job, and binds it to the given packet
     *
     * @param mixed $packetID
     */
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

    /**
     * Deletes the given packet
     *
     * @param mixed $packetID
     */
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
    /**
     * Deletes the company identified with $slug
     *
     * @param mixed $slug
     */
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

    /**
     * Returns all jobs that are owned by a company identified with 
     * $companySlugname
     *
     * @param mixed $companySlugName
     */
    public function getJobsWithCompanySlugName($companySlugName)
    {
        $return = $this->getJobMapper()->findJobsWithCompanySlugName($companySlugName);

        return $return;
    }

    /**
     * Returns all companies identified with $slugName
     *
     * @param mixed $slugName
     */
    public function getCompaniesWithSlugName($slugName)
    {
        return $this->getCompanyMapper()->findCompaniesWithSlugName($slugName);
    }

    /**
     * Returns a persistent packet
     *
     * @param mixed $packetID
     */
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

    /**
     * Returns all companies with a given $slugName and makes them persistent
     *
     * @param mixed $slugName
     */
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

    /**
     * Returns all jobs with a given slugname, owned by a company with 
     * $companySlugName
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     */
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

    /**
     * Returns all jobs with a $jobSlugName, owned by a company with a 
     * $companySlugName
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     */
    public function getJobsWithSlugName($companySlugName, $jobSlugName)
    {
        return $this->getJobMapper()->findJobWithSlugName($companySlugName, $jobSlugName);
    }

    /**
     * Returns all jobs in the database
     *
     */
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

    /**
     * Returns a the form for entering packets
     *
     */
    public function getPacketForm()
    {
        return $this->sm->get('company_admin_edit_packet_form');
    }

    /**
     * Returns the form for entering jobs
     *
     */
    public function getJobForm()
    {
        return $this->sm->get('company_admin_edit_job_form');
    }

    /**
     * Returns all jobs that are active
     *
     */
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

    /**
     * Returns the companyMapper
     *
     */
    public function getCompanyMapper()
    {
        return $this->sm->get('company_mapper_company');
    }

    /**
     * Returns the packetMapper
     *
     */
    public function getPacketMapper()
    {
        return $this->sm->get('company_mapper_packet');
    }

    /**
     * Returns the jobMapper
     *
     */
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
