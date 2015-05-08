<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Model\Member as MemberModel;
use Decision\Mapper\Member as MemberMapper;

/**
 * User service.
 */
class Member extends AbstractAclService
{

    /**
     * Get member by id.
     *
	 * @Param $id lidnr of the member to find
     * @return Member.
     */
    public function getMemberByLidNr($id)
    {
        if (!$this->isAllowed('find')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to fetch members from database.')
            );
        }

        return $this->getMemberMapper()->findByLidnr($id);
    }

    /**
     * Get the member mapper.
     *
     * @return MemberMapper.
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
