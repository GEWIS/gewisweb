<?php

declare(strict_types=1);

namespace User\Permissions\Resource;

use Decision\Model\Member as MemberModel;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

interface OwnerResourceInterface extends ResourceInterface
{
    /**
     * Get the owner (a member) of this resource.
     */
    public function getResourceOwner(): MemberModel;
}
