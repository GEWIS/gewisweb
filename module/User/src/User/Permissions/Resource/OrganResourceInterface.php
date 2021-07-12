<?php

namespace User\Permissions\Resource;

use Laminas\Permissions\Acl\Resource\ResourceInterface;

use Decision\Model\Organ;

interface OrganResourceInterface extends ResourceInterface
{
    /**
     * Get the organ of this resource.
     *
     * @return Organ
     */
    public function getResourceOrgan();
}
