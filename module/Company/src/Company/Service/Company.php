<?php

namespace Company\Service;

use Application\Service\AbstractService;

use Company\Model\Company as CompanyModel;
use Company\Mapper\Company as CompanyMapper;
/**
 * User service.
 */
class Company extends AbstractService
{
    public function getCompanyList() {
        return $this->getCompanyMapper()->findAll();
        //return array('Apple', 'Microsoft');
    }
    public function getCompanyMapper()
    {
        return $this->sm->get('company_mapper_company');
    }
}