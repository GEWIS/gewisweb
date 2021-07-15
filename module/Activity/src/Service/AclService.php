<?php


namespace Activity\Service;


use User\Permissions\Assertion\IsCreatorOrOrganMember;

class AclService extends \User\Service\AclService
{
    protected function createAcl()
    {
        parent::createAcl();

        $this->acl->addResource('activity');
        $this->acl->addResource('activityApi');
        $this->acl->addResource('myActivities');
        $this->acl->addResource('model');
        $this->acl->addResource('activity_calendar_proposal');
        $this->acl->addResource('signupList');

        $this->acl->allow('guest', 'activity', ['view', 'viewCategory']);
        $this->acl->allow('guest', 'signupList', ['view', 'externalSignup']);

        $this->acl->allow('user', 'activity_calendar_proposal', ['create', 'delete_own']);
        $this->acl->allow('admin', 'activity_calendar_proposal', ['create_always', 'delete_all', 'approve']);

        $this->acl->allow('user', 'myActivities', 'view');
        $this->acl->allow(
            'user',
            'signupList',
            ['view', 'viewDetails', 'signup', 'signoff', 'checkUserSignedUp']
        );

        $this->acl->allow('active_member', 'activity', ['create', 'viewAdmin', 'listCategories']);
        $this->acl->allow(
            'active_member',
            'activity',
            ['update', 'viewDetails', 'adminSignup', 'viewParticipants', 'exportParticipants'],
            new IsCreatorOrOrganMember()
        );
        $this->acl->allow(
            'active_member',
            'signupList',
            ['adminSignup', 'viewParticipants', 'exportParticipants'],
            new IsCreatorOrOrganMember()
        );

        $this->acl->allow('sosuser', 'signupList', ['signup', 'signoff', 'checkUserSignedUp']);

        $this->acl->allow('user', 'activityApi', 'list');
        $this->acl->allow('apiuser', 'activityApi', 'list');
    }
}
