<?php

declare(strict_types=1);

namespace Education\Service;

use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Override;

class AclService extends \User\Service\AclService
{
    #[Override]
    protected function createAcl(): void
    {
        parent::createAcl();

        // add resource
        $this->acl->addResource(new Resource('education'));
        $this->acl->addResource(new Resource('course_document'));
        $this->acl->addResource(new Resource('course'));
        // Define administration part of this module, however, sub-permissions must be manually configured.
        $this->acl->addResource(new Resource('education_admin'));

        // users (logged in GEWIS members) are allowed to view
        // exams besides users, also people on the TU/e network are
        // allowed to view and download exams (users inherit from
        // tueguest)
        $this->acl->allow('tueguest', 'course_document', ['view', 'download']);
    }
}
