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
        if (!$this->isAllowed('list')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed list the companies')
            );
        }
        return $this->getCompanyMapper()->findAll($translator->getLocale());
    }
    // Company list for admin interface
    /**
     * Returns a list of all companies (including hidden companies)
     *
     */
    public function getHiddenCompanyList()
    {
        if (!$this->isAllowed('listall')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to acces the admin interface')
            );
        }
        return $this->getCompanyMapper()->findAll();
    }

    /**
     * Checks if the data is valid, and if it is saves the package
     *
     * @param mixed $package
     * @param mixed $data
     */
    public function savePackageByData($package,$data)
    {
        $packageForm = $this->getPackageForm();
        $packageForm->setData($data);
        if ($packageForm->isValid()){
            $package->exchangeArray($data);
            $this->savePackage();
        }
    }

    /**
     * Checks if the data is valid, and if it is, saves the Company
     *
     * @param mixed $company
     * @param mixed $data
     */
    public function saveCompanyByData($company,$data)
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
    public function saveJobByData($job,$data)
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
     * Saves all modified packages
     *
     */
    public function savePackage()
    {
        $this->getPackageMapper()->save();
    }

    /**
     * Checks if the data is valid, and if it is, inserts the company, and sets
     * all data
     *
     * @param mixed $data
     */
    public function insertCompanyByData($data)
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
        if (!$this->isAllowed('insert')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a company')
            );
        }
        return $this->getCompanyMapper()->insert($languages);
    }

    /**
     * Checks if the data is valid, and if it is, inserts the package, and assigns it to the given company
     *
     * @param mixed $companySlugName
     * @param mixed $data
     */
    public function insertPackageForCompanySlugNameByData($companySlugName,$data)
    {
        $packageForm = $this->getPackageForm();
        $packageForm->setData($data);
        if ($packageForm->isValid()) {
            $package = $this->insertPackageForCompanySlugName($companySlugName);
            $package->exchangeArray($data);
            $this->savePackage();
            return true;
        }
        return false;
    }

    /**
     * Inserts a package and assigns it to the given company
     *
     * @param mixed $companySlugName
     */
    public function insertPackageForCompanySlugName($companySlugName)
    {
        if (!$this->isAllowed('insert')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a package')
            );
        }
        $companies = $this->getEditableCompaniesBySlugName($companySlugName);
        var_dump($companySlugName);
        $company = $companies[0];
        return $this->getPackageMapper()->insertPackageIntoCompany($company);
    }

    /**
     * Checks if the data is valid, and if it is, assigns a job, and bind it to
     * the given package
     *
     * @param mixed $packageID
     * @param mixed $data
     */
    public function insertJobIntoPackageIDByData($packageID,$data)
    {
        $jobForm = $this->getJobForm();
        $jobForm->setData($data);
        if ($jobForm->isValid()) {
            $job = $this->insertJobIntoPackageID($packageID);
            $job->exchangeArray($data);
            $this->saveCompany();
            return $job;
        }
        return null;
    }

    /**
     * Inserts a job, and binds it to the given package
     *
     * @param mixed $packageID
     */
    public function insertJobIntoPackageID($packageID)
    {
        if (!$this->isAllowed('insert')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a job')
            );
        }
        $package = $this->getEditablePackage($packageID);
        $result = $this->getJobMapper()->insertIntoPackage($package);

        return $result;
    }

    /**
     * Deletes the given package
     *
     * @param mixed $packageID
     */
    public function deletePackage($packageID)
    {
        if (!$this->isAllowed('delete')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete packages')
            );
        }
        return $this->getPackageMapper()->delete($packageID);
    }

    /**
     * Deletes the company identified with $slug
     *
     * @param mixed $slug
     */
    public function deleteCompaniesBySlug($slug)
    {
        if (!$this->isAllowed('delete')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete companies')
            );
        }
        return $this->getCompanyMapper()->deleteBySlug($slug);
    }

    /**
     * Returns all companies identified with $slugName
     *
     * @param mixed $slugName
     */
    public function getCompaniesBySlugName($slugName)
    {
        return $this->getCompanyMapper()->findCompaniesBySlugName($slugName);
    }

    /**
     * Returns a persistent package
     *
     * @param mixed $packageID
     */
    public function getEditablePackage($packageID)
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit packages')
            );
        }
        if (is_null($packageID)){
            throw new \Exception('Invalid arguemnt');
        }
        $package = $this->getPackageMapper()->findEditablePackage($packageID);

        return $package;
    }

    /**
     * Returns all companies with a given $slugName and makes them persistent
     *
     * @param mixed $slugName
     */
    public function getEditableCompaniesBySlugName($slugName)
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit companies')
            );
        }
        return $this->getCompanyMapper()->findEditableCompaniesBySlugName($slugName, true);
    }

    /**
     * Returns all jobs with a given slugname, owned by a company with
     * $companySlugName
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     */
    public function getEditableJobsBySlugName($companySlugName, $jobSlugName)
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit jobs')
            );
        }
        return $this->getJobMapper()->findJobBySlugName($companySlugName, $jobSlugName);
    }

    /**
     * Returns all jobs with a $jobSlugName, owned by a company with a
     * $companySlugName
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     */
    public function getJobsBySlugName($companySlugName, $jobSlugName)
    {
        return $this->getJobMapper()->findJobBySlugName($companySlugName, $jobSlugName);
    }

    /**
     * Returns all jobs with owned by a company with a
     * $companySlugName
     *
     * @param mixed $companySlugName
     */
    public function getJobsByCompanyName($companySlugName)
    {
        return $this->getJobMapper()->findJobByCompanySlugName($companySlugName);
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
     * Returns a the form for entering packages
     *
     */
    public function getPackageForm()
    {
        return $this->sm->get('company_admin_edit_package_form');
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
        $jobList = $this->getJobList();
        $array = [];
        foreach ($jobList as $job) {
            if ($job->isActive()) {
                $array[] = $job;
            }
        }

        return $array;
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
     * Returns the packageMapper
     *
     */
    public function getPackageMapper()
    {
        return $this->sm->get('company_mapper_package');
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
