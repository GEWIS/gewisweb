<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;

/**
 * Poll service.
 */
class Poll extends AbstractAclService
{




    /**
     * Get the poll mapper.
     *
     * @return \Frontpage\Mapper\Poll
     */
    public function getPollMapper()
    {
        return $this->sm->get('frontpage_mapper_poll');
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('frontpage_acl');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'poll';
    }
}
