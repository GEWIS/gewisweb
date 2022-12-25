<?php

namespace Company\Service;

class AclService extends \User\Service\AclService
{
    protected function createAcl(): void
    {
        parent::createAcl();

        // Add resources
        $this->acl->addResource('company');
        $this->acl->addResource('job');
        $this->acl->addResource('jobCategory');
        $this->acl->addResource('jobLabel');
        $this->acl->addResource('package');

        // Guests can view banners and featured companies. They can also view and list only visible companies and
        // (their) jobs. Additionally, they can list (see) any visible categories and/or labels on jobs.
        $this->acl->allow(
            roles: 'guest',
            resources: 'company',
            privileges: ['viewBanner', 'viewFeatured'],
        );
        $this->acl->allow(
            roles: 'guest',
            resources: ['company', 'job'],
            privileges: ['list', 'view'],
        );
        $this->acl->allow(
            roles: 'guest',
            resources: ['jobCategory', 'jobLabel'],
            privileges: 'list',
        );

        // Company admins are able to view all companies (even invisible ones). They can also create and edit companies,
        // jobs, job categories, job labels, and packages. Furthermore, they can delete companies, jobs, and packages.
        // Additionally, they may approve edits to companies and jobs. Finally, they can list all categories and labels
        // (even invisible ones).
        $this->acl->allow(
            roles: 'company_admin',
            resources: 'company',
            privileges: 'listAll',
        );

        $this->acl->allow(
            roles: 'company_admin',
            resources: ['company', 'job', 'jobCategory', 'jobLabel', 'package'],
            privileges: ['create', 'edit'],
        );
        $this->acl->allow(
            roles: 'company_admin',
            resources: ['company', 'job', 'package'],
            privileges: 'delete',
        );
        $this->acl->allow(
            roles: 'company_admin',
            resources: ['company', 'job'],
            privileges: 'approve',
        );

        $this->acl->allow(
            roles: 'company_admin',
            resources: ['jobCategory', 'jobLabel'],
            privileges: 'listAll',
        );

        // Company users can view and edit their own company account. They can also create, edit, and delete jobs.
        // Additionally, they can view all job labels.
        // TODO: Make this an assertion to ensure a CompanyUser can only edit their own company (`Own` is temporary):
        $this->acl->allow(
            roles: 'company',
            resources: 'company',
            privileges: 'editOwn',
        );
        $this->acl->allow(
            roles: 'company',
            resources: 'company',
            privileges: 'viewAccount',
        );

        // TODO: Make this an assertion to ensure a CompanyUser can only edit their own jobs (`Own` is temporary):
        $this->acl->allow(
            roles: 'company',
            resources: 'job',
            privileges: ['createOwn', 'editOwn', 'deleteOwn'],
        );

        $this->acl->allow(
            roles: 'company',
            resources: 'jobLabel',
            privileges: 'listAll',
        );
    }
}
