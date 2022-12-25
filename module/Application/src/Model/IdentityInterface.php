<?php

namespace Application\Model;

use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

interface IdentityInterface extends RoleInterface, ResourceInterface
{
}
