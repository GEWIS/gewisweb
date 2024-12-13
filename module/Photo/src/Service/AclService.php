<?php

declare(strict_types=1);

namespace Photo\Service;

use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use User\Permissions\Assertion\IsAfterMembershipEndedAndNotTagged;

class AclService extends \User\Service\AclService
{
    protected function createAcl(): void
    {
        parent::createAcl();

        // add resources for this module
        $this->acl->addResource(new Resource('photo'));
        $this->acl->addResource(new Resource('album'));
        $this->acl->addResource(new Resource('tag'));
        $this->acl->addResource(new Resource('vote'));
        // Define administration part of this module, however, sub-permissions must be manually configured.
        $this->acl->addResource(new Resource('photo_admin'));

        // Only users and 'the screen' are allowed to view photos (and its details) and albums
        $this->acl->allow('user', 'album', ['view', 'search']);
        $this->acl->allow('user', 'photo', ['view', 'download', 'view_metadata']);

        $this->acl->allow('apiuser', 'photo', 'view');
        $this->acl->allow('apiuser', 'album', 'view');

        // Users are allowed to view, remove and add tags, and view and add their votes
        $this->acl->allow('user', 'tag', ['view', 'add', 'remove']);
        $this->acl->allow('user', 'vote', ['view', 'add']);

        // Graduates may not view photos/albums that were made after their membership ended.
        $this->acl->deny('graduate', 'album', 'view', new IsAfterMembershipEndedAndNotTagged());
        $this->acl->deny(
            'graduate',
            'photo',
            ['view', 'download', 'view_metadata'],
            new IsAfterMembershipEndedAndNotTagged(),
        );
        // Graduates may not tag people or vote for the photo of the week. This applies to all photos.
        $this->acl->deny('graduate', 'tag', ['add', 'remove']);
        $this->acl->deny('graduate', 'vote', 'add');
    }
}
