<?php


namespace Company\Service;

use Application\Service\AbstractAclService;
use Company\Model\ApprovalModel\ApprovalPending;
use Company\Model\ApprovalModel\ApprovalProfile;


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
     * Get all pendingApprovals with the given profile id
     *
     * @param $id Int
     * @return mixed
     */
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
        return $this->getApprovalMapper()->findEditableCompaniesBySlugName($slugName, true);
    }

    /**
     * Find the approvalProfile for the given id
     *
     * @param $id Int id of the to be found approvalProfile
     * @return ApprovalProfile
     */
    public function getApprovalProfileById($id) {
        return $this->getApprovalMapper()->findApprovalProfileById($id);
    }

    /**
     * Delete a pending approval model and the corresponding approval models.
     *
     * @param $deletionInfo array containing info of the pending approval model to be deleted
     */
    public function deletePendingApproval($deletionInfo){
        //check type
        if($deletionInfo["type"] === "banner"){
            $this->getApprovalMapper()->deletePendingApproval($deletionInfo["approvalId"]);
        }elseif($deletionInfo["type"] === "vacancy"){
            $vacancyApprovals = $this->getEditableVacanciesByLanguageNeutralId($deletionInfo["languageNeutralId"]);
            $this->deleteVacancyApprovals($vacancyApprovals);
        }elseif ($deletionInfo["type"] === "profile"){
            $profileApproval = $this->getApprovalProfileById($deletionInfo["profileApprovalId"])[0];
            $this->deleteProfileApprovals($profileApproval);
        }
    }


    /**
     * Delete a list of approvalVacancies and the corresponding approvalPending entries
     *
     * @param $vacancyApprovals array of the approvalVacancies which need to be deleted
     */
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

    /**
     * Delete an approvalProfile and the corresponding approvalPending and approvalCompanyI18n entries
     *
     * @param $profileApproval ApprovalProfile to be deleted
     */
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
        //$this->getApprovalMapper()->removeApproval($profileApproval);
    }

    /**
     * Get companyI18 models for the given company id
     *
     * @param $cId Int company id
     * @return mixed
     */
    public function getApprovalCompanyI18($cId){
        return $this->getApprovalMapper()->findApprovalCompanyI18($cId);
    }

    /**
     * Set Profile approval and corresponding Pending approval to rejected
     *
     * @param $pId Int profile approval id
     */
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

    /**
     * Set Vacancy approval and corresponding Pending approval to rejected
     *
     * @param $vId Int vacancy approval id
     */
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

    /**
     * Get Vacancy Approvals by language neutral id
     *
     * @param $languageNeutralId Int language neutral ids of the to be found vacancies
     * @return array of vacancies with the given language neutral id
     */
    public function getEditableVacanciesByLanguageNeutralId($languageNeutralId) {
        return $this->getApprovalMapper()->findVacanciesByLanguageNeutralId($languageNeutralId);
    }

    /**
     * Get banner by id
     *
     * @param $id Int banner Id
     * @return array containing the banner approval
     */
    public function getBannerApprovalById($id){
        return $this->getApprovalMapper()->findBannerApprovalById($id);
    }

    /**
     * Reject banner Approval
     *
     * @param $id Int banner Id
     * @return void
     */
    public function rejectBannerApproval($id){
        //This doesn't actually return anything right? so can't return be removed?
        //TODO: whoever made this function look at this
        return $this->getApprovalMapper()->rejectBannerApproval($id);
    }

    /**
     * Accept banner approval
     *
     * @param $id Int banner id
     * @param $approvalId Int pending approval Id
     */
    public function acceptBannerApproval($id, $approvalId){
        $this->getApprovalMapper()->acceptBannerApproval($id, $approvalId);
    }

    /**
     * Return if a vacancy has been approved
     *
     * @param $vacancyId Int vacancy Id
     * @return boolean
     */
    public function getApprovedByVacancyId($vacancyId) {
        return $this->getApprovalMapper()->findApprovedByVacancyId($vacancyId);
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
