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
     * Retrieves all organs which the current user is allowed to edit.
     *
     * @return array
     */
    public function getEditableOrgans()
    {
        if (!$this->isAllowed('edit')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit organ information')
            );
        }

        if ($this->isAllowed('editall')) {
            return array_merge(
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_COMMITTEE),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_FRATERNITY)
            );
        }

        $user = $this->sm->get('user_service_user')->getIdentity();

        //TODO: filter out avc's
        return $this->getMemberMapper()->findOrgans($user->getMember());
    }

    /**
     * Checks if the current user is allowed to edit the given organ.
     *
     * @return bool
     */
    public function canEditOrgan($organ)
    {
        if (!$this->isAllowed('edit')) {
            return false;
        }

        if ($this->isAllowed('editall')) {
            return true;
        }

        $user = $this->sm->get('user_service_user')->getIdentity();

        foreach ($this->getMemberMapper()->findOrgans($user->getMember()) as $memberOrgan) {
            if ($memberOrgan->getId() === $organ->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $type either committee, avc or fraternity
     *
     * @return array
     */
    public function findActiveOrgansByType($type)
    {
        return $this->getOrganMapper()->findActive($type);
    }

    /**
     * @param string $type either committee, avc or fraternity
     *
     * @return array
     */
    public function findAbrogatedOrgansByType($type)
    {
        return $this->getOrganMapper()->findAbrogated($type);
    }

    /**
     * Finds an organ by its abbreviation
     *
     * @param $abbr
     *
     * @return OrganModel
     */
    public function findOrganByAbbr($abbr)
    {
        return $this->getOrganMapper()->findByAbbr($abbr);
    }

    /**
     * @param integer $organId
     *
     * @param array $post POST Data
     * @param array $files FILES Data
     *
     * @return bool
     */
    public function updateOrganInformation($organId, $post, $files)
    {
        $organInformation = $this->getEditableOrganInformation($organId);
        $form = $this->getOrganInformationForm($organInformation);

        $data = array_merge_recursive($post->toArray(), $files->toArray());

        $form->setData($data);
        if (!$form->isValid()) {
            return false;
        }

        if ($files['upload']['size'] > 0) {
            $coverPath = $this->getFileStorageService()->storeUploadedFile($files['upload']);
            $organInformation->setCoverPath($coverPath);
        }

        $this->getEntityManager()->flush();

        if ($this->isAllowed('approve')) {
            $this->approveOrganInformation($organInformation);
        }

        $this->getEmailService()->sendEmail('organ_update', 'email/organUpdate',
            'Een orgaan heeft een update doorgevoerd | An organ has updated her page',
            ['organInfo' => $organInformation]);

        return true;
    }

    public function approveOrganInformation($organInformation)
    {
        $em = $this->getEntityManager();
        $oldInformation = $organInformation->getOrgan()->getApprovedOrganInformation();
        if (!is_null($oldInformation)) {
            $em->remove($oldInformation);
        }
        $user = $em->merge($this->sm->get('user_service_user')->getIdentity());
        $organInformation->setApprover($user);
        $em->flush();
    }

    /**
     * Get the OrganInformation form.
     *
     * @param \Decision\Model\OrganInformation $organInformation
     *
     * @return \Decision\Form\OrganInformation|bool
     */
    public function getOrganInformationForm($organInformation)
    {
        if (!$this->canEditOrgan($organInformation->getOrgan())) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit this organ\'s information')
            );
        }

        $form = $this->sm->get('decision_form_organ_information');
        $form->bind($organInformation);

        return $form;
    }

    public function getEditableOrganInformation($organId)
    {
        $organ = $this->getOrgan($organId); //TODO: catch exception
        if (is_null($organ)) {
            return false;
        }
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
            $organInformation->setOrgan($organ);
            $em->persist($organInformation);
            $organ->getOrganInformation()->add($organInformation);

            return $organInformation;
        }

        /*
         * Create an unapproved clone of the organ information
         */
        $organInformation = clone $organInformation;
        $organInformation->setApprover(null);
        $em->detach($organInformation);
        $em->persist($organInformation);
        $organ->getOrganInformation()->add($organInformation);

        return $organInformation;
    }

    /**
     * Returns a list of an organ's current and previous members including their function.
     *
     * @param OrganModel $organ
     *
     * @return array
     */
    public function getOrganMemberInformation($organ)
    {
        $oldMembers = [];
        $currentMembers = [];
        foreach ($organ->getMembers() as $install) {
            if (null === $install->getDischargeDate()) {
                // current member
                if (!isset($currentMembers[$install->getMember()->getLidnr()])) {
                    $currentMembers[$install->getMember()->getLidnr()] = [
                        'member' => $install->getMember(),
                        'functions' => []
                    ];
                }
                if ($install->getFunction() != 'Lid') {
                    $function = $this->getTranslator()->translate($install->getFunction());
                    $currentMembers[$install->getMember()->getLidnr()]['functions'][] = $function;
                }
            } else {
                // old member
                if (!isset($oldMembers[$install->getMember()->getLidnr()])) {
                    $oldMembers[$install->getMember()->getLidnr()] = $install->getMember();
                }
            }
        }
        $oldMembers = array_filter($oldMembers, function ($member) use ($currentMembers) {
            return !isset($currentMembers[$member->getLidnr()]);
        });

        // Sort members by function
        usort($currentMembers, function ($a, $b) {
            if ($a['functions'] == $b['functions']) {
                return 0;
            }

            if (count($a['functions']) > count($b['functions'])) {
                return -1;
            }

            return in_array('Voorzitter', $a['functions']) ? -1 : 1;
        });

        return [
            'oldMembers' => $oldMembers,
            'currentMembers' => $currentMembers
        ];
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
     * Get the member mapper.
     *
     * @return \Decision\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->sm->get('decision_mapper_member');
    }

    /**
     * Gets the storage service.
     *
     * @return \Application\Service\Storage
     */
    public function getFileStorageService()
    {
        return $this->sm->get('application_service_storage');
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

    /**
     * Get the email service.
     *
     * @return \Application\Service\Email
     */
    public function getEmailService()
    {
        return $this->sm->get('application_service_email');
    }
}
