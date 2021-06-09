<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Model\vacancy as companyAccountModel;

use Zend\Http\Client as HttpClient;

class Settings extends AbstractAclService
{


    /**
     * Get all available company user information given a company id
     *
     * @param integer $id the id of the company who's user information
     * will be fetched.
     *
     * @return array CompanyUser model
     */
    public function getCompanyUser($id){
        echo var_dump($this->getcompanyAccountMapper()->findCompanyUser($id));
    }


    /**
     * Get all available company information given a company id
     *
     * @param integer $id the id of the company who's information
     * will be fetched.
     *
     * @return array Company model
     */
    public function getCompanyInfo($id){
        return $this->getcompanyAccountMapper()->findCompanyInfo($id);
    }

    /**
     * Get all available company package information
     *
     * @param string $cName the name of the company who's package information
     * will be fetched.
     *
     * @return array package Information of company
     */
    public function getCompanyPackageInfo($id){
        return $this->getcompanyAccountMapper()->findCompanyPackageInfo($id);
    }

    /**
     * Update the companies information given a number of changed values
     *
     * @param string $collumns the columns in Company table that will be altered
     *
     * @param string $values the new values for the to be altered collumns
     *
     * @param string $id the id of the company who's company information
     * will be altered.
     *
     * @return null
     */
    public function updateCompanyData($collumns, $values, $id){
        $this->getcompanyAccountMapper()->setCompanyData($collumns, $values, $id);
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'settings';
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

    /**
     * Get the CompanyAccount mapper.
     *
     * @return \Decision\Mapper\CompanyAccount
     */
    public function getcompanyAccountMapper()
    {
        return $this->sm->get('decision_mapper_settings');
    }
}
