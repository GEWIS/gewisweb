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
        if (!$this->isAllowed('view')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view membership info.')
            );
        }
        return $this->getMemberMapper()->findByLidnr($this->getRole()->getLidnr());
    }

    /**
     *
     */
    public function findMemberByLidNr($lidnr)
    {
        return $this->getMemberMapper()->findByLidnr($lidnr);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param string $query (part of) the full name of a member
     * @pre $name must be at least 3 characters
     *
     * @return array|null
     */
    public function searchMembersByName($query)
    {
        if (strlen($query) < 3) {
            throw new \Zend\Code\Exception\InvalidArgumentException(
                $this->getTranslator()->translate('Name must be at least 3 characters')
            );
        }

        if (!$this->isAllowed('search')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to search for members.')
            );
        }

        return $this->getMemberMapper()->searchByName($query);

    }

    /**
     * Get the member mapper.
     *
     * @return \Decision\Mapper\Member
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
