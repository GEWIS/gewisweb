<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Model\Organ as OrganModel;
use Decision\Mapper\Organ as OrganMapper;

/**
 * User service.
 */
class Organ extends AbstractAclService
{

    /**
     * Get organs.
     *
     * @return array Of organs.
     */
    public function getOrgans()
    {
        if (!$this->isAllowed('view')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view the list of organs.')
            );
        }

        return $this->getOrganMapper()->findActive();
    }

    /**
     * Get one organ.
     *
     * @param int $id
     *
     * @return OrganModel
     */
    public function getOrgan($id)
    {
        if (!$this->isAllowed('show')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view organ information')
            );
        }

        return $this->getOrganMapper()->find($id);
    }

    /**
     * Get the organ mapper.
     *
     * @return OrganMapper.
     */
    public function getOrganMapper()
    {
        return $this->sm->get('decision_mapper_organ');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'organ';
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
