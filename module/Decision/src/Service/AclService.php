<?php


namespace Decision\Service;


class AclService extends \User\Service\AclService
{
    protected function createAcl()
    {
        parent::createAcl();

        // add resources for this module
        $this->acl->addResource('organ');
        $this->acl->addResource('member');
        $this->acl->addResource('dreamspark');
        $this->acl->addResource('decision');
        $this->acl->addResource('meeting');
        $this->acl->addResource('authorization');
        $this->acl->addResource('files');
        $this->acl->addResource('regulations');

        // users are allowed to view the organs
        $this->acl->allow('guest', 'organ', 'list');
        $this->acl->allow('user', 'organ', 'view');

        // Organ members are allowed to edit organ information of their own organs
        $this->acl->allow('active_member', 'organ', ['edit', 'viewAdmin']);

        // guests are allowed to view birthdays on the homepage
        $this->acl->allow('guest', 'member', 'birthdays_today');

        // users are allowed to view and search members
        $this->acl->allow('user', 'member', ['view', 'view_self', 'search', 'birthdays']);
        $this->acl->allow('apiuser', 'member', ['view']);

        $this->acl->allow('user', 'decision', ['search', 'view_meeting', 'list_meetings']);

        $this->acl->allow('user', 'meeting', ['view', 'view_notes', 'view_documents']);

        $this->acl->allow('user', 'dreamspark', ['login', 'students']);

        $this->acl->allow('user', 'authorization', ['create', 'view_own']);

        // users are allowed to use the filebrowser
        $this->acl->allow('user', 'files', 'browse');

        // users are allowed to download the regulations
        $this->acl->allow('user', 'regulations', ['list', 'download']);
    }
}
