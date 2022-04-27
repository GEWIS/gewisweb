<?php

namespace Photo\Service;

class AclService extends \User\Service\AclService
{
    protected function createAcl(): void
    {
        parent::createAcl();

        // add resources for this module
        $this->acl->addResource('photo');
        $this->acl->addResource('album');
        $this->acl->addResource('tag');
        $this->acl->addResource('vote');

        // Only users and 'the screen' are allowed to view photos and albums
        $this->acl->allow('user', 'photo', 'view');
        $this->acl->allow('user', 'album', 'view');

        $this->acl->allow('apiuser', 'photo', 'view');
        $this->acl->allow('apiuser', 'album', 'view');

        // Users are allowed to view, remove and add tags
        $this->acl->allow('user', 'tag', ['view', 'add', 'remove']);

        // Users are allowed to view and add votes
        $this->acl->allow('user', 'tag', ['view', 'add']);

        // Users are allowed to download photos
        $this->acl->allow('user', 'photo', ['download', 'view_metadata']);

        $this->acl->allow('photo_guest', 'photo', 'view');
        $this->acl->allow('photo_guest', 'album', 'view');
        $this->acl->allow('photo_guest', 'photo', ['download', 'view_metadata']);
    }
}
