<?php

namespace Education\Service;

class AclService extends \User\Service\AclService
{
    protected function createAcl()
    {
        parent::createAcl();

        // add resource
        $this->acl->addResource('exam');

        // users (logged in GEWIS members) are allowed to view
        // exams besides users, also people on the TU/e network are
        // allowed to view and download exams (users inherit from
        // tueguest)
        $this->acl->allow('tueguest', 'exam', ['view', 'download']);
    }
}
