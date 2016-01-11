<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Model\Organ as OrganModel;
use Decision\Mapper\Organ as OrganMapper;
use Decision\Model\OrganInformation;

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
        if (!$this->isAllowed('list')) {
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
        if (!$this->isAllowed('view')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view organ information')
            );
        }

        return $this->getOrganMapper()->find($id);
    }

    /**
     * @param integer $organId
     * @param array $data form post data
     * @return bool
     */
    public function updateOrganInformation($organId, $data)
    {
        $form = $this->getOrganInformationForm($organId);
        if (!$form) {
            return false;
        }

        $form->setData($data);
        if (!$form->isValid()) {
            return false;
        }

        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Get the OrganInformation form.
     *
     * @param integer $organId
     *
     * @return \Organ\Form\OrganInformation|bool
     */
    public function getOrganInformationForm($organId)
    {
        if (!$this->isAllowed('edit')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit organ information.')
            );
        }
        $form = $this->sm->get('decision_form_organ_information');
        $organ = $this->getOrgan($organId);
        if (is_null($organ)) {
            return false;
        }
        $organInformation = $this->getEditableOrganInformation($organ);

        $form->bind($organInformation);

        return $form;
    }

    public function getEditableOrganInformation($organ)
    {
        $em = $this->getEntityManager();
        $organInformation = null;
        foreach ($organ->getOrganInformation() as $information) {
            if (is_null($information->getApprover())) {
                return $information;
            }
            $organInformation = $information;
        }

        if (is_null($organInformation)) {
            $organInformation = new OrganInformation();
            $em->persist($organInformation);
            return $organInformation;
        }

        /*
         * Create an unapproved clone of the organ information
         */
        $organInformation = clone $organInformation;
        $organInformation->setApprover(null);
        $em->detach($organInformation);
        $em->persist($organInformation);

        return $organInformation;
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
     * Get the entity manager
     */
    public function getEntityManager()
    {
        return $this->sm->get('doctrine.entitymanager.orm_default');
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
