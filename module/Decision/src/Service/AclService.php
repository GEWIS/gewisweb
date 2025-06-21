<?php

declare(strict_types=1);

namespace Decision\Service;

use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Override;

class AclService extends \User\Service\AclService
{
    #[Override]
    protected function createAcl(): void
    {
        parent::createAcl();

        // add resources for this module
        $this->acl->addResource(new Resource('organ'));
        $this->acl->addResource(new Resource('member'));
        $this->acl->addResource(new Resource('decision'));
        $this->acl->addResource(new Resource('meeting'));
        $this->acl->addResource(new Resource('authorization'));
        $this->acl->addResource(new Resource('files'));
        $this->acl->addResource(new Resource('regulations'));
        $this->acl->addResource(new Resource('gdpr'));
        // Define administration part of this module, however, sub-permissions must be manually configured.
        $this->acl->addResource(new Resource('decision_admin'));
        $this->acl->addResource(new Resource('decision_organ_admin'));

        // users are allowed to view the organs
        $this->acl->allow('guest', 'organ', 'list');
        $this->acl->allow('user', 'organ', 'view');

        // Organ members are allowed to edit organ information of their own organs
        $this->acl->allow('active_member', 'organ', 'edit');
        $this->acl->allow('active_member', 'decision_organ_admin', 'view');

        // users are allowed to view and search members
        $this->acl->allow('user', 'member', ['view', 'view_self', 'search', 'birthdays']);
        $this->acl->allow('apiuser', 'member', ['view']);

        $this->acl->allow('user', 'decision', ['search', 'view_meeting', 'list_meetings']);

        $this->acl->allow('user', 'meeting', ['view', 'view_minutes', 'view_documents']);

        $this->acl->allow('user', 'authorization', ['create', 'revoke', 'view_own']);

        // users are allowed to use the filebrowser
        $this->acl->allow('user', 'files', 'browse');

        // users are allowed to download the regulations
        $this->acl->allow('user', 'regulations', ['list', 'download']);

        // graduates may not do a few things, so limit them.
        $this->acl->deny('graduate', 'member', ['view', 'search', 'birthdays']);
        $this->acl->deny('graduate', 'authorization', ['create', 'revoke', 'view_own']);

        // do not allow board to perform GDPR requests
        $this->acl->deny('board', 'gdpr');
    }
}
