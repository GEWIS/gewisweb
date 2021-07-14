<?php

namespace User\Permissions\Resource;

use Laminas\Permissions\Acl\Resource\ResourceInterface;
use User\Model\User;

interface CreatorResourceInterface extends ResourceInterface
{
    /**
     * Get the creator (a user) of this resource.
     *
     * @return User
     */
    public function getResourceCreator();
}
