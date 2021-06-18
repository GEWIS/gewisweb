<?php


namespace Company\Mapper;

use Company\Model\ApprovalModel\ApprovalPending;
use Company\Model\ApprovalModel\ApprovalProfile;
use Company\Model\ApprovalModel\ApprovalCompanyI18n;
use Company\Model\ApprovalModel\ApprovalVacancy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class Approval
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function persist($job)
    {
        $this->em->persist($job);
        $this->em->flush();
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Saves all modified entities that are marked persistant
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Delete the given Approval Model
     *
     * @param mixed $approval approval model to be removed
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeApproval($approval)
    {
        $this->em->remove($approval);
        $this->em->flush();
    }

    /**
     * Find all pending approvals
     *
     * @return array ApprovalPending model
     */
    public function findPendingApprovals()
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\ApprovalModel\ApprovalPending', 'ap');

        $select = $builder->generateSelectClause(['ap' => 't1']);
        $sql = "SELECT $select FROM ApprovalPending AS t1";
        $query = $this->em->createNativeQuery($sql, $builder);

        return $query->getResult();
    }

    public function findBannerApprovalById($id){
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\ApprovalModel\ApprovalPending', 'ap');

        $select = $builder->generateSelectClause(['ap' => 't1']);
        $sql = "SELECT $select FROM ApprovalPending AS t1".
        " WHERE t1.id = $id AND t1.type = 'banner'";
        $query = $this->em->createNativeQuery($sql, $builder);

        return $query->getResult();
    }

    public function rejectBannerApproval($id){
        $qb = $this->em->createQueryBuilder();
        $qb->update("Company\Model\ApprovalModel\ApprovalPending", "ap");
        $qb->where("ap.id = $id");
        $qb->set("ap.rejected", ":rejected");
        $qb->setParameter("rejected", "1");
        $qb->getQuery()->getResult();
    }

    public function findApprovalProfileById($id) {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.id=:id');
        $qb->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }



    public function acceptBannerApproval($id, $approvalId){
        $qb = $this->em->createQueryBuilder();
        $qb->update("Company\Model\CompanyPackage", "cp");
        $qb->where("cp.id = $id");
        $qb->set("cp.published", ":published");
        $qb->setParameter("published", "1");
        $qb->getQuery()->getResult();

        $qb = $this->em->createQueryBuilder();
        $qb->delete("Company\Model\ApprovalModel\ApprovalPending", "ap");
        $qb->where("ap.id = $approvalId");
        $qb->getQuery()->getResult();


    }

    public function deletePendingApproval($approvalId){
        $qb = $this->em->createQueryBuilder();
        $qb->delete("Company\Model\ApprovalModel\ApprovalPending", "ap");
        $qb->where("ap.id = $approvalId");
        $qb->getQuery()->getResult();
    }

    public function deleteBannerApproval($approvalId){
        $this->deletePendingApproval($approvalId);
    }

    public function deleteProfileApproval($approvalId, $companyId){
        //$this->deletePendingApproval($approvalId);

        $qb = $this->em->createQueryBuilder();
        $qb->delete("Company\Model\ApprovalModel\ApprovalCompanyI18n", "ac");
        $qb->where("ac.company = $companyId");
        $qb->getQuery()->getResult();

//        $qb = $this->em->createQueryBuilder();
//        $qb->delete("Company\Model\ApprovalModel\ApprovalProfile", "ap");
//        $qb->where("ap.company = $companyId");
//        $qb->getQuery()->getResult();



    }

    public function deleteVacancyApproval($approvalId, $packageId){
        $this->deletePendingApproval($approvalId);

        $qb = $this->em->createQueryBuilder();
        $qb->delete("Company\Model\ApprovalModel\ApprovalVacancy", "av");
        $qb->where("av.package = $packageId");
        $qb->getQuery()->getResult();

    }


        /**
     * Find the company with the given slugName.
     *
     * @param slugName The 'username' of the company to get.
     * @param asObject if yes, returns the company as an object in an array, otherwise returns the company as an array of an array
     *
     * @return An array of companies with the given slugName.
     */
    public function findEditableCompaniesBySlugName($slugName, $asObject)
    {

        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.slugName=:slugCompanyName');
        $qb->setParameter('slugCompanyName', $slugName);
        $qb->setMaxResults(1);
        if ($asObject) {
            return $qb->getQuery()->getResult();
        }

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * Find the Pending approval with the given vacancy approval id.
     *
     * @param Int $id The vacancy id of the pending approval to get.
     *
     * @return Array An array containing the pending approvals with the given vacancy id.
     */
    public function findPendingVacancyApprovalById($id)
    {
        $objectRepository = $this->getPendingRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.VacancyApproval=:id');
        $qb->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find if a vacancy has been approved
     *
     * @param Int $id The vacancy id of the pending approval to get.
     *
     * @return Array An array containing the pending approvals with the given vacancy id.
     */
    public function findApprovedByVacancyId($vacancyId)
    {
        $objectRepository = $this->getPendingRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.VacancyApproval=:id')
            ->setParameter('id', $vacancyId);

        $vacancy = $qb->getQuery()->getResult();

        if (empty($vacancy)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Find the Profile approval with the given profile approval id.
     *
     * @param Int $id The profile id of the pending approval to get.
     *
     * @return Array An array containing the pending approvals with the given profile id.
     */
    public function findPendingProfileApprovalById($id)
    {
        $objectRepository = $this->getPendingRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.ProfileApproval=:id');
        $qb->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the Pending approval with the given id.
     *
     * @param Int $id The id of the pending approval to get.
     *
     * @return Array An array containing the pending approvals with the given id.
     */
    public function findPendingApprovalById($id)
    {

        $objectRepository = $this->getPendingRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.id=:id');
        $qb->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }

    public function findPendingApprovalByProfile($id)
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\ApprovalModel\ApprovalPending', 'ap');

        $select = $builder->generateSelectClause(['ap' => 't1']);
        $sql = "SELECT $select FROM ApprovalPending AS t1".
        " WHERE t1.ProfileApproval_id = $id";
        $query = $this->em->createNativeQuery($sql, $builder);

        return $query->getResult();
    }

    /**
     * Find the Profile approval with the given id.
     *
     * @param Int $id The profile id
     *
     * @return Array An array containing the profile approvals with the given id.
     */
    public function findProfileApprovalById($id)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.id=:id');
        $qb->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the Vacancy approval with the given id.
     *
     * @param Int $id The vacancy id
     *
     * @return Array An array containing the vacancy approvals with the given id.
     */
    public function findVacancyApprovalById($id)
    {
        $objectRepository = $this->getVacancyRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.id=:id');
        $qb->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }


    /**
     * Find the company with the given slugName.
     *
     * @param slugName The 'username' of the company to get.
     * @param asObject if yes, returns the company as an object in an array, otherwise returns the company as an array of an array
     *
     * @return An array of companies with the given slugName.
     */
    public function findEditableCompaniesBySlugName2($slugName, $asObject)
    {

        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\ApprovalModel\ApprovalProfile', 'ci');

        $select = $builder->generateSelectClause(['ci' => 't1']);
        $sql = "SELECT $select FROM ApprovalProfile AS t1".
            " WHERE t1.slugName = '$slugName'";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }

    public function findApprovalCompanyI18($cId){
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Company\Model\ApprovalModel\ApprovalCompanyI18n', 'ci');

        $select = $builder->generateSelectClause(['ci' => 't1']);
        $sql = "SELECT $select FROM ApprovalCompanyI18n AS t1".
            " WHERE t1.company_id = $cId";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\ApprovalModel\ApprovalProfile');
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getVacancyRepository()
    {
        return $this->em->getRepository('Company\Model\ApprovalModel\ApprovalVacancy');
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getPendingRepository()
    {
        return $this->em->getRepository('Company\Model\ApprovalModel\ApprovalPending');
    }

    /**
     * Get the a job by it's id
     *
     * @return ApprovalVacancy
     */
    public function findVacanciesByLanguageNeutralId($vacancy_id) {
        $qb = $this->getVacancyRepository()->createQueryBuilder('j');
        $qb->select('j');
        $qb->where('j.languageNeutralId =:vacancy_id');
        $qb->setParameter('vacancy_id', $vacancy_id);
        return $qb->getQuery()->getResult();
    }

    /**
     * Inserts a company into the datebase, and initializes the given
     * translations as empty translations for them
     *
     * @param mixed $languages
     */
    public function insert($languages)
    {
        $company = new ApprovalProfile($this->em);

        foreach ($languages as $language) {
            $translation = new ApprovalCompanyI18n($language, $company);
            if (is_null($translation->getLogo())) {
                $translation->setLogo('');
            }
            $this->em->persist($translation);
            $company->addTranslation($translation);
        }

        $this->em->persist($company);

        return $company;
    }




}
