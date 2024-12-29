<?php

declare(strict_types=1);

namespace Frontpage\Service;

use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use User\Permissions\Assertion\IsAllowedToViewPage;

class AclService extends \User\Service\AclService
{
    protected function createAcl(): void
    {
        parent::createAcl();

        $this->acl->addResource(new Resource('page'));
        $this->acl->addResource(new Resource('poll'));
        $this->acl->addResource(new Resource('poll_comment'));
        $this->acl->addResource(new Resource('news_item'));
        $this->acl->addResource(new Resource('infimum'));
        // Define administration part of this module, however, sub-permissions must be manually configured.
        $this->acl->addResource(new Resource('frontpage_admin'));

        $this->acl->allow('user', 'infimum', 'view');
        $this->acl->allow('user', 'poll', ['vote', 'request']);
        $this->acl->allow('user', 'poll_comment', ['view', 'create', 'list']);

        $this->acl->allow(null, 'page', 'view', new IsAllowedToViewPage());
    }
}
