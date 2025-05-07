<?php

declare(strict_types=1);

namespace Activity\Service;

use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Override;
use User\Permissions\Assertion\IsCreatorOrOrganMember;

class AclService extends \User\Service\AclService
{
    #[Override]
    protected function createAcl(): void
    {
        parent::createAcl();

        $this->acl->addResource(new Resource('activity'));
        $this->acl->addResource(new Resource('activityApi'));
        $this->acl->addResource(new Resource('myActivities'));
        $this->acl->addResource(new Resource('model'));
        $this->acl->addResource(new Resource('activity_calendar_period'));
        $this->acl->addResource(new Resource('activity_calendar_proposal'));
        $this->acl->addResource(new Resource('signupList'));
        // Define administration part of this module, however, sub-permissions must be manually configured.
        $this->acl->addResource(new Resource('activity_admin'));

        $this->acl->allow('guest', 'activity', ['view', 'viewCategory']);
        $this->acl->allow('guest', 'signupList', ['view', 'externalSignup']);

        $this->acl->allow('admin', 'activity_calendar_period', ['create', 'edit', 'delete', 'view']);

        $this->acl->allow('user', 'activity_calendar_proposal', ['create', 'delete_own']);
        $this->acl->allow('admin', 'activity_calendar_proposal', ['create_always', 'delete_all', 'approve']);

        $this->acl->allow('user', 'myActivities', 'view');
        $this->acl->allow(
            'user',
            'signupList',
            ['view', 'viewDetails', 'signup', 'signoff', 'checkUserSignedUp'],
        );

        $this->acl->allow('active_member', 'activity', ['create', 'listCategories']);
        $this->acl->allow(
            'active_member',
            'activity',
            ['update', 'viewDetails', 'adminSignup', 'viewParticipants', 'exportParticipants'],
            new IsCreatorOrOrganMember(),
        );
        $this->acl->allow(
            'active_member',
            'signupList',
            ['adminSignup', 'viewParticipants', 'exportParticipants'],
            new IsCreatorOrOrganMember(),
        );
        $this->acl->allow('active_member', 'activity_admin', 'view');

        $this->acl->allow('admin', 'activity', 'viewParticipantDetails');
        $this->acl->allow('admin', 'activity', 'approve');

        $this->acl->allow('user', 'activityApi', 'list');
        $this->acl->allow('apiuser', 'activityApi', 'list');
    }
}
