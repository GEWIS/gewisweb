<?php

namespace Activity\Service;

use Application\Service\AbstractAclService;

use Activity\Model\Activity as ActivityModel;

class Activity extends AbstractAclService
{

    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        //todo, this;
        return $this->getServiceManager()->get('education_acl');
    }

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activity';
    }
}
