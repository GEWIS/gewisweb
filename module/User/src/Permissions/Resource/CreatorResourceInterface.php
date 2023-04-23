<?php

declare(strict_types=1);

namespace User\Permissions\Resource;

use Decision\Model\Member as MemberModel;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

interface CreatorResourceInterface extends ResourceInterface
{
    /**
     * Get the creator (a member) of this resource.
     *
     * @return MemberModel
     */
    public function getResourceCreator(): MemberModel;
}
