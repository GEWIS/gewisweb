<?php


namespace Company\Service;


class AclService extends \User\Service\AclService
{
    protected function createAcl()
    {
        parent::createAcl();

        // add resource
        $this->acl->addResource('company');

        $this->acl->allow('guest', 'company', 'viewFeaturedCompany');
        $this->acl->allow('guest', 'company', 'list');
        $this->acl->allow('guest', 'company', 'view');
        $this->acl->allow('guest', 'company', 'listVisibleCategories');
        $this->acl->allow('guest', 'company', 'listVisibleLabels');
        $this->acl->allow('guest', 'company', 'showBanner');
        $this->acl->allow('company_admin', 'company', ['insert', 'edit', 'delete']);
        $this->acl->allow('company_admin', 'company', ['listall', 'listAllCategories', 'listAllLabels']);
    }
}
