<?php

namespace User\Permissions\Resource;

use Decision\Model\Organ;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

interface OrganResourceInterface extends ResourceInterface
{
    /**
     * Get the organ of this resource.
     *
     * @return Organ
     */
    public function getResourceOrgan();
}
