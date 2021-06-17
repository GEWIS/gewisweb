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

    public function getPendingApprovalByProfile($id){
        return $this->getApprovalMapper()->findPendingApprovalByProfile($id);
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

    public function getApprovalProfileById($id) {
        return $this->getApprovalMapper()->findApprovalProfileById($id);
    }

    public function deletePendingApproval($deletionInfo){
        //check type
        if($deletionInfo["type"] === "banner"){
            $this->getApprovalMapper()->deleteBannerApproval($deletionInfo["approvalId"]);
        }elseif($deletionInfo["type"] === "vacancy"){
            $vacancyApprovals = $this->getEditableVacanciesByLanguageNeutralId($deletionInfo["languageNeutralId"]);
            $this->deleteVacancyApprovals($vacancyApprovals);
            //$this->getApprovalMapper()->deleteVacancyApproval($deletionInfo["approvalId"], $deletionInfo["packageId"]);
        }elseif ($deletionInfo["type"] === "profile"){
            $profileApproval = $this->getApprovalProfileById($deletionInfo["profileApprovalId"])[0];
            $this->deleteProfileApprovals($profileApproval);
            //$this->getApprovalMapper()->deleteProfileApproval($deletionInfo["approvalId"], $deletionInfo["companyId"]);
        }
    }


    public function deleteVacancyApprovals($vacancyApprovals) {
        // Remove ApprovalVacancyEntries
        foreach ($vacancyApprovals as $approval) {
            // Get ApprovalPending entry for the VacancyApproval
            $id = $approval->getId();
            $pending = $this->getApprovalMapper()->findPendingVacancyApprovalById($id)[0];

            // Delete the approvals
            $this->getApprovalMapper()->removeApproval($pending);
            $this->getApprovalMapper()->removeApproval($approval);
        }
    }

    public function deleteProfileApprovals($profileApproval) {
        // Get ApprovalPending entry for the VacancyApproval
        $id = $profileApproval->getId();
        $pending = $this->getApprovalMapper()->findPendingProfileApprovalById($id)[0];

        // Get the ApprovalCompanyI18n entries for the company
        $languages = $this->getApprovalMapper()->findApprovalCompanyI18($id);

        // Delete the approvals
        foreach ($languages as $lang) {
            $this->getApprovalMapper()->removeApproval($lang);
        }
        $this->getApprovalMapper()->removeApproval($pending);
        $this->getApprovalMapper()->removeApproval($profileApproval);
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

    public function rejectBannerApproval($id){
        return $this->getApprovalMapper()->rejectBannerApproval($id);
    }

    public function acceptBannerApproval($id, $approvalId){
        return $this->getApprovalMapper()->acceptBannerApproval($id, $approvalId);
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
