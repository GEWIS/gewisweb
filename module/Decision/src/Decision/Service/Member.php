<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

/**
 * Member service.
 */
class Member extends AbstractAclService
{

    /**
     * Obtain information about the current user.
     *
     * @return Decision\Model\Member
     */
    public function getMembershipInfo()
    {
        return $this->getMemberMapper()->findByLidnr($this->getRole()->getLidnr());
    }

    /**
     * Get the member mapper.
     *
     * @return Decision\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->sm->get('decision_mapper_member');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'member';
    }

    /**
     * Get the Acl.
     *
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('decision_acl');
    }
}
