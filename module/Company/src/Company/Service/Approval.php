<?php


namespace Company\Service;

use Application\Service\AbstractAclService;


class Approval extends AbstractAclService
{

    /**
     * Get all pending approvals
     *
     * @return array ApprovalPending model
     */
    public function getPendingApprovals(){
        return $this->getApprovalMapper()->findPendingApprovals();
    }

    /**
     * Returns all companies with a given $slugName and makes them persistent
     *
     * @param mixed $slugName
     */
    public function getEditableCompaniesBySlugName($slugName)
    {

//        if (!$this->isAllowed('edit')) {
//
//            $translator = $this->getTranslator();
//            throw new \User\Permissions\NotAllowedException(
//                $translator->translate('You are not allowed to edit companies')
//            );
//        }
//
        return $this->getApprovalMapper()->findEditableCompaniesBySlugName($slugName, true);
    }

    public function getApprovalCompanyI18($cId){
        return $this->getApprovalMapper()->findApprovalCompanyI18($cId);
    }

    public function rejectProfileApproval($pId){
        $profileApproval = $this->getApprovalMapper()->findProfileApprovalById($pId)[0];
        $profileApproval->setRejected(true);
        $this->getApprovalMapper()->persist($profileApproval);
        $this->getApprovalMapper()->save();

        $pendingApproval = $this->getApprovalMapper()->findPendingProfileApprovalById($profileApproval->getId())[0];
        $pendingApproval->setRejected(true);
        $this->getApprovalMapper()->persist($pendingApproval);
        $this->getApprovalMapper()->save();
    }

    public function rejectVacancyApproval($vId){
        $vacancyApproval = $this->getApprovalMapper()->findVacancyApprovalById($vId)[0];
        $vacancyApproval->setRejected(true);
        $this->getApprovalMapper()->persist($vacancyApproval);
        $this->getApprovalMapper()->save();

        $pendingApproval = $this->getApprovalMapper()->findPendingVacancyApprovalById($vacancyApproval->getId())[0];
        $pendingApproval->setRejected(true);
        $this->getApprovalMapper()->persist($pendingApproval);
        $this->getApprovalMapper()->save();
    }

    public function getEditableVacanciesByLanguageNeutralId($languageNeutralId) {
        return $this->getApprovalMapper()->findVacanciesByLanguageNeutralId($languageNeutralId);
    }

    public function getBannerApprovalById($id){
        return $this->getApprovalMapper()->findBannerApprovalById($id);
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'approval';
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
     * Get the CompanyAccount mapper.
     *
     * @return \Decision\Mapper\CompanyAccount
     */
    public function getApprovalMapper()
    {
        return $this->sm->get('company_mapper_approval');
    }
}
