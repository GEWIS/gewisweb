<?php

namespace Company\Service;

//
use Application\Service\AbstractAclService;
use Application\Service\FileStorage;
use Company\Form\EditCategory;
use Company\Form\EditCompany;
use Company\Form\EditJob;
use Company\Form\EditLabel;
use Company\Form\EditPackage;
use Company\Mapper\BannerPackage;
use Company\Mapper\Category;
use Company\Mapper\FeaturedPackage;
use Company\Mapper\Label;
use Company\Mapper\LabelAssignment;
use Company\Mapper\Package;
use Company\Model\Job;
use Company\Model\Job as JobModel;
use Company\Model\JobCategory as CategoryModel;
use Company\Model\JobLabel as LabelModel;
use Company\Model\JobLabelAssignment;
use DateTime;
use Exception;
use InvalidArgumentException;
use User\Model\User;
use User\Permissions\NotAllowedException;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;

/**
 * Company service.
 */
class Company extends AbstractACLService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var FileStorage
     */
    private $storageService;

    /**
     * @var \Company\Mapper\Company
     */
    private $companyMapper;

    /**
     * @var Package
     */
    private $packageMapper;

    /**
     * @var BannerPackage
     */
    private $bannerPackageMapper;

    /**
     * @var FeaturedPackage
     */
    private $featuredPackageMapper;

    /**
     * @var \Company\Mapper\Job
     */
    private $jobMapper;

    /**
     * @var Category
     */
    private $categoryMapper;

    /**
     * @var CompanyQuery
     */
    private $labelMapper;

    /**
     * @var LabelAssignment
     */
    private $labelAssignmentMapper;

    /**
     * @var EditCompany
     */
    private $editCompanyForm;

    /**
     * @var EditPackage
     */
    private $editPackageForm;

    /**
     * @var EditPackage
     */
    private $editBannerPackageForm;

    /**
     * @var EditPackage
     */
    private $editFeaturedPackageForm;

    /**
     * @var EditJob
     */
    private $editJobForm;

    /**
     * @var EditCategory
     */
    private $editCategoryForm;

    /**
     * @var EditLabel
     */
    private $editLabelForm;

    /**
     * @var array
     */
    private $languages;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        FileStorage $storageService,
        \Company\Mapper\Company $companyMapper,
        Package $packageMapper,
        BannerPackage $bannerPackageMapper,
        FeaturedPackage $featuredPackageMapper,
        \Company\Mapper\Job $jobMapper,
        Category $categoryMapper,
        Label $labelMapper,
        LabelAssignment $labelAssignmentMapper,
        EditCompany $editCompanyForm,
        EditPackage $editPackageForm,
        EditPackage $editBannerPackageForm,
        EditPackage $editFeaturedPackageForm,
        EditJob $editJobForm,
        EditCategory $editCategoryForm,
        EditLabel $editLabelForm,
        array $languages
    )
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->storageService = $storageService;
        $this->companyMapper = $companyMapper;
        $this->packageMapper = $packageMapper;
        $this->bannerPackageMapper = $bannerPackageMapper;
        $this->featuredPackageMapper = $featuredPackageMapper;
        $this->jobMapper = $jobMapper;
        $this->categoryMapper = $categoryMapper;
        $this->labelMapper = $labelMapper;
        $this->labelAssignmentMapper = $labelAssignmentMapper;
        $this->editCompanyForm = $editCompanyForm;
        $this->editPackageForm = $editPackageForm;
        $this->editBannerPackageForm = $editBannerPackageForm;
        $this->editFeaturedPackageForm = $editFeaturedPackageForm;
        $this->editJobForm = $editJobForm;
        $this->editCategoryForm = $editCategoryForm;
        $this->editLabelForm = $editLabelForm;
        $this->languages = $languages;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Returns a random banner for display on the frontpage
     *
     */
    public function getCurrentBanner()
    {

        if (!$this->isAllowed('showBanner')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the banner')
            );
        }

        return $this->bannerPackageMapper->getBannerPackage();
    }

    public function getFeaturedPackage()
    {

        if (!$this->isAllowed('viewFeaturedCompany')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the featured company')
            );
        }

        return $this->featuredPackageMapper->getFeaturedPackage($this->translator->getLocale());
    }

    private function getFuturePackageStartsBeforeDate($date)
    {
        $startPackages = array_merge(
            $this->packageMapper->findFuturePackageStartsBeforeDate($date),
            $this->bannerPackageMapper->findFuturePackageStartsBeforeDate($date),
            $this->featuredPackageMapper->findFuturePackageStartsBeforeDate($date)
        );

        usort($startPackages, function ($a, $b) {
            $aStart = $a->getStartingDate();
            $bStart = $b->getStartingDate();
            if ($aStart == $bStart) {
                return 0;
            }
            return $aStart < $bStart ? -1 : 1;
        });

        return $startPackages;
    }

    private function getFuturePackageExpiresBeforeDate($date)
    {
        $expirePackages = array_merge(
            $this->packageMapper->findFuturePackageExpirationsBeforeDate($date),
            $this->bannerPackageMapper->findFuturePackageExpirationsBeforeDate($date),
            $this->featuredPackageMapper->findFuturePackageExpirationsBeforeDate($date)
        );

        usort($expirePackages, function ($a, $b) {
            $aEnd = $a->getExpirationDate();
            $bEnd = $b->getExpirationDate();
            if ($aEnd == $bEnd) {
                return 0;
            }
            return $aEnd < $bEnd ? -1 : 1;
        });

        return $expirePackages;
    }

    /**
     * Searches for packages that change before $date
     *
     * @param date The date until where to search
     * @return Two sorted arrays, containing the packages that respectively start and expire between now and $date,
     */
    public function getPackageChangeEvents($date)
    {


        if (!$this->isAllowed('listall')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to list the companies')
            );
        }

        $startPackages = $this->getFuturePackageStartsBeforeDate($date);
        $expirePackages = $this->getFuturePackageExpiresBeforeDate($date);

        return [$startPackages, $expirePackages];
    }

    /**
     * Returns an list of all companies (excluding hidden companies)
     *
     */
    public function getCompanyList()
    {


        if (!$this->isAllowed('list')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to list the companies')
            );
        }

        return $this->companyMapper->findPublicByLocale($this->translator->getLocale());
    }
    // Company list for admin interface

    /**
     * Returns a list of all companies (including hidden companies)
     *
     */
    public function getHiddenCompanyList()
    {
        if (!$this->isAllowed('listall')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to access the admin interface')
            );
        }

        return $this->companyMapper->findAll();
    }

    /**
     * Get public company by id.
     *
     * @param $id
     * @return \Company\Model\Company|null
     */
    public function getCompanyById($id)
    {

        if (!$this->isAllowed('listall')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to list the companies')
            );
        }

        return $this->companyMapper->findById($id);
    }

    public function categoryForSlug($slug)
    {

        $mapper = $this->categoryMapper;
        $category = $mapper->findCategory($slug);
        $locale = $this->translator->getLocale();

        if ($category === null && $slug == "jobs") {
            $category = $mapper->createNullCategory($this->translator->getLocale(), $this->translator);
        }
        if ($category === null || $category->getLanguage() == $locale) {
            return $category;
        }

        return $mapper->siblingCategory($category, $locale);
    }

    /**
     * Creates a new JobCategory.
     *
     * @param array $data Category data from the EditCategory form
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     * @throws NotAllowedException When a user is not allowed to create a job category
     *
     */
    public function createCategory($data)
    {
        if (!$this->isAllowed('insert')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to insert a job category')
            );
        }

        $categoryDict = [];
        foreach ($this->languages as $lang) {
            $category = new CategoryModel();
            $category->setLanguage($lang);
            $categoryDict[$lang] = $category;
        }

        return $this->saveCategoryData("", $categoryDict, $data);
    }

    /**
     * Checks if the data is valid, and if it is, saves the JobCategory
     *
     * @param int|string $languageNeutralId Identifier of the JobCategories to save
     * @param array $categories The JobCategories to save
     * @param array $data The (new) data to save
     *
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     */
    public function saveCategoryData($languageNeutralId, $categories, $data)
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit job categories')
            );
        }

        $categoryForm = $this->getCategoryForm();
        $categoryForm->bind($categories);
        $categoryForm->setData($data);

        if (!$categoryForm->isValid()) {
            return false;
        }

        $id = -1;
        foreach ($categories as $category) {
            $id = $this->setLanguageNeutralCategoryId($id, $category, $languageNeutralId);
            $this->categoryMapper->persist($category);
            $this->saveCategory();
        }

        return (($languageNeutralId == "") ? $id : $languageNeutralId);
    }

    /**
     * Sets the languageNeutralId for this JobCategory.
     *
     * @param int $id The id of the JobCategory
     * @param CategoryModel $category The JobCategory
     * @param int|string $languageNeutralId The languageNeutralId of the JobCategory
     *
     * @return int
     */
    private function setLanguageNeutralCategoryId($id, $category, $languageNeutralId)
    {
        if ($languageNeutralId == "") {
            $category->setLanguageNeutralId($id);
            $this->categoryMapper->persist($category);
            $this->saveCategory();

            if ($id == -1) {
                $id = $category->getId();
            }

            $category->setLanguageNeutralId($id);
            return $id;
        }

        $category->setLanguageNeutralId($languageNeutralId);

        return $id;
    }

    /**
     * Creates a new JobLabel.
     *
     * @param array $data Label data from the EditLabel form
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     * @throws NotAllowedException When a user is not allowed to create a job label
     *
     */
    public function createLabel($data)
    {
        if (!$this->isAllowed('insert')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to insert a job label')
            );
        }

        $labelDict = [];
        foreach ($this->languages as $lang) {
            $label = new LabelModel();
            $label->setLanguage($lang);
            $labelDict[$lang] = $label;
        }

        return $this->saveLabelData("", $labelDict, $data);
    }

    /**
     * Checks if the data is valid, and if it is, saves the JobLabel
     *
     * @param int|string $languageNeutralId Identifier of the JobLabel to save
     * @param array $labels The JobLabels to save
     * @param array $data The data to validate, and apply to the label
     *
     * @return bool|int
     */
    public function saveLabelData($languageNeutralId, $labels, $data)
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit job labels')
            );
        }

        $labelForm = $this->getLabelForm();
        $labelForm->bind($labels);
        $labelForm->setData($data);

        if (!$labelForm->isValid()) {
            return false;
        }

        $id = -1;
        foreach ($labels as $label) {
            $id = $this->setLanguageNeutralLabelId($id, $label, $languageNeutralId);
            $this->labelMapper->persist($label);
            $this->saveLabel();
        }

        return (($languageNeutralId == "") ? $id : $languageNeutralId);
    }

    /**
     * Sets the languageNeutralId for this JobLabel.
     *
     * @param int $id The id of the JobLabel
     * @param LabelModel $label The JobLabel
     * @param int|string $languageNeutralId The languageNeutralId of the JobLabel
     *
     * @return int
     */
    private function setLanguageNeutralLabelId($id, $label, $languageNeutralId)
    {
        if ($languageNeutralId == "") {
            $label->setLanguageNeutralId($id);
            $this->labelMapper->persist($label);
            $this->saveLabel();

            if ($id == -1) {
                $id = $label->getId();
            }

            $label->setLanguageNeutralId($id);
            return $id;
        }

        $label->setLanguageNeutralId($languageNeutralId);

        return $id;
    }

    /**
     * Checks if the data is valid, and if it is saves the package
     *
     * @param mixed $package
     * @param mixed $data
     */
    public function savePackageByData($package, $data, $files)
    {
        $packageForm = $this->getPackageForm();
        $packageForm->setData($data);
        if ($packageForm->isValid()) {
            $package->exchangeArray($data);
            if ($package->getType() == 'banner') {
                $file = $files['banner'];
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        return false;
                    }
                    $oldPath = $package->getImage();
                    $newPath = $this->storageService->storeUploadedFile($file);
                    $package->setImage($newPath);
                    if ($oldPath != '' && $oldPath != $newPath) {
                        $this->storageService->removeFile($oldPath);
                    }
                }
            }
            $this->savePackage();
            return true;
        }
    }

    /**
     * Checks if the data is valid, and if it is, saves the Company
     *
     * @param \Company\Model\Company $company
     * @param array $data
     */
    public function saveCompanyByData($company, $data, $files)
    {
        $companyForm = $this->editCompanyForm;
        $mergedData = array_merge_recursive(
            $data->toArray(),
            $files->toArray()
        );
        $companyForm->setData($mergedData);
        if ($companyForm->isValid()) {
            $company->exchangeArray($data);
            foreach ($company->getTranslations() as $translation) {
                $file = $files[$translation->getLanguage() . '_logo'];
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        return false;
                    }
                    $oldPath = $translation->getLogo();
                    $newPath = $this->storageService->storeUploadedFile($file);
                    $translation->setLogo($newPath);
                    if ($oldPath !== '' && $oldPath != $newPath) {
                        $this->storageService->removeFile($oldPath);
                    }
                }
            }
            $this->saveCompany();
            return true;
        }
    }

    /**
     * Saves all modified categories
     *
     */
    public function saveCategory()
    {
        $this->categoryMapper->save();
    }

    /**
     * Saves all modified labels
     *
     */
    public function saveLabel()
    {
        $this->labelMapper->save();
    }

    /**
     * Saves all modified jobs
     *
     */
    public function saveJob()
    {
        $this->jobMapper->save();
    }

    /**
     * Saves all modified companies
     *
     */
    public function saveCompany()
    {
        $this->companyMapper->save();
    }

    /**
     * Saves all modified packages
     *
     */
    public function savePackage()
    {
        $this->packageMapper->save();
    }

    /**
     * Checks if the data is valid, and if it is, inserts the company, and sets
     * all data
     *
     * @param \Company\Model\Company $data
     */
    public function insertCompanyByData($data, $files)
    {
        $companyForm = $this->editCompanyForm;
        $mergedData = array_merge_recursive(
            $data->toArray(),
            $files->toArray()
        );
        $companyForm->setData($mergedData);

        if ($companyForm->isValid()) {
            $company = $this->insertCompany($data['languages']);
            $company->exchangeArray($data);
            foreach ($company->getTranslations() as $translation) {
                $file = $files[$translation->getLanguage() . '_logo'];
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        return false;
                    }
                    $newPath = $this->storageService->storeUploadedFile($file);
                    $translation->setLogo($newPath);
                }
            }
            $this->saveCompany();
            return $company;
        }

        return null;
    }

    /**
     * Inserts the company and initializes translations for the given languages
     *
     * @param mixed $languages
     */
    public function insertCompany($languages)
    {
        if (!$this->isAllowed('insert')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to insert a company')
            );
        }

        return $this->companyMapper->insert($languages);
    }

    /**
     * Checks if the data is valid, and if it is, inserts the package, and assigns it to the given company
     *
     * @param mixed $companySlugName
     * @param mixed $data
     */
    public function insertPackageForCompanySlugNameByData($companySlugName, $data, $files, $type = "job")
    {
        $packageForm = $this->getPackageForm($type);
        $packageForm->setData($data);
        if ($packageForm->isValid()) {
            $package = $this->insertPackageForCompanySlugName($companySlugName, $type);
            if ($type === 'banner') {
                $newPath = $this->storageService->storeUploadedFile($files);
                $package->setImage($newPath);
            }
            $package->exchangeArray($data);
            $this->savePackage();
            return true;
        }

        return false;
    }

    /**
     * Inserts a package and assigns it to the given company
     *
     * @param mixed $companySlugName
     */
    public function insertPackageForCompanySlugName($companySlugName, $type = "job")
    {
        if (!$this->isAllowed('insert')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to insert a package')
            );
        }

        $companies = $this->getEditableCompaniesBySlugName($companySlugName);
        $company = $companies[0];

        return $this->packageMapper->insertPackageIntoCompany($company, $type);
    }

    /**
     * Creates a new job and adds it to the specified package.
     *
     * @param integer $packageId
     * @param array $data
     * @param array $files
     * @return bool
     */
    public function createJob($packageId, $data, $files)
    {
        $package = $this->packageMapper->findPackage($packageId);
        $jobs = [];

        foreach ($this->languages as $lang) {
            $job = new JobModel();
            $job->setPackage($package);
            $job->setLanguage($lang);
            $jobs[$lang] = $job;
        }

        return $this->saveJobData("", $jobs, $data, $files);
    }

    /**
     * Checks if the data is valid, and if it is, saves the Job
     *
     * @param int|string $languageNeutralId Identifier of the Job to save
     * @param array $jobs The Job to save
     * @param array $data The (new) data to save
     * @param array $files The (new) files to save
     *
     * @return bool
     */
    public function saveJobData($languageNeutralId, $jobs, $data, $files)
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit jobs')
            );
        }

        $jobForm = $this->getJobForm();
        $mergedData = array_merge_recursive(
            $data->toArray(),
            $files->toArray()
        );
        $jobForm->setCompanySlug(current($jobs)->getCompany()->getSlugName());
        $jobForm->setCurrentSlug($data['slugName']);
        $jobForm->bind($jobs);
        $jobForm->setData($mergedData);

        if (!$jobForm->isValid()) {
            return false;
        }
        $id = -1;

        $labelIds = $data['labels'];
        if (is_null($labelIds)) {
            $labelIds = [];
        }

        foreach ($jobs as $lang => $job) {
            $file = $files['jobs'][$lang]['attachment_file'];

            if ($file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $oldPath = $job->getAttachment();

                try {
                    $newPath = $this->storageService->storeUploadedFile($file);
                } catch (Exception $e) {
                    return false;
                }

                if (!is_null($oldPath) && $oldPath != $newPath) {
                    $this->storageService->removeFile($oldPath);
                }

                $job->setAttachment($newPath);
            }

            $job->setTimeStamp(new DateTime());
            $id = $this->setLanguageNeutralJobId($id, $job, $languageNeutralId);
            $this->jobMapper->persist($job);
            $this->saveJob();

            $mapper = $this->labelMapper;
            $lang = $job->getLanguage();
            // Contains language specific labels
            $labelsLangBased = [];
            foreach ($labelIds as $labelId) {
                $label = $mapper->findLabelById($labelId);
                $labelsLangBased[] = $mapper->siblingLabel($label, $lang)->getId();
            }
            $this->setLabelsForJob($job, $labelsLangBased);
        }

        return true;
    }

    /**
     * @param Job $job
     * @param array $labels
     */
    private function setLabelsForJob($job, $labels)
    {
        $mapper = $this->labelAssignmentMapper;
        $currentAssignments = $mapper->findAssignmentsByJobId($job->getId());
        $currentLabels = [];
        foreach ($currentAssignments as $labelAsg) {
            $currentLabels[] = $labelAsg->getLabel()->getId();
        }
        $intersection = array_intersect($labels, $currentLabels);
        $toRemove = array_diff($currentLabels, $labels);
        $toAdd = array_diff($labels, $intersection);

        $this->removeLabelsFromJob($job, $toRemove);
        $this->addLabelsToJob($job, $toAdd);
    }

    /**
     * @param Job $job
     * @param array $labels
     */
    private function addLabelsToJob($job, $labels)
    {
        $mapperLabel = $this->labelMapper;
        $mapperLabelAssignment = $this->labelAssignmentMapper;
        $mapperJob = $this->jobMapper;
        foreach ($labels as $label) {
            $jobLabelAssignment = new JobLabelAssignment();
            $labelModel = $mapperLabel->findLabelById($label);
            $jobLabelAssignment->setLabel($labelModel);
            $job->addLabel($jobLabelAssignment);
            $mapperLabelAssignment->persist($jobLabelAssignment);
            $mapperJob->flush();
        }
    }

    /**
     * @param Job $job
     * @param array $labels
     */
    private function removeLabelsFromJob($job, $labels)
    {
        $mapper = $this->labelAssignmentMapper;
        foreach ($labels as $label) {
            $toRemove = $mapper->findAssignmentByJobIdAndLabelId($job->getId(), $label);
            $mapper->delete($toRemove);
        }
    }

    private function setLanguageNeutralJobId($id, $job, $languageNeutralId)
    {
        if ($languageNeutralId == "") {
            $job->setLanguageNeutralId($id);
            $this->jobMapper->persist($job);
            $this->saveJob();

            if ($id == -1) {
                $id = $job->getId();
            }
            $job->setLanguageNeutralId($id);
            return $id;
        }
        $job->setLanguageNeutralId($languageNeutralId);

        return $id;
    }

    /**
     * Inserts a job, and binds it to the given package
     *
     * @param mixed $packageId
     */
    public function insertJobIntoPackageId($packageId, $lang, $languageNeutralId)
    {
        if (!$this->isAllowed('insert')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to insert a job')
            );
        }
        $package = $this->getEditablePackage($packageId);

        return $this->jobMapper->insertIntoPackage($package, $lang, $languageNeutralId);
    }

    /**
     * Deletes the given package
     *
     * @param mixed $packageId
     */
    public function deletePackage($packageId)
    {
        if (!$this->isAllowed('delete')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete packages')
            );
        }
        $this->packageMapper->delete($packageId);
        $this->bannerPackageMapper->delete($packageId);
    }

    /**
     * Deletes the given job
     *
     * @param mixed $packageId
     */
    public function deleteJob($jobId)
    {
        if (!$this->isAllowed('delete')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete jobs')
            );
        }
        $this->jobMapper->deleteByLanguageNeutralId($jobId);
    }

    /**
     * Deletes the company identified with $slug
     *
     * @param mixed $slug
     */
    public function deleteCompaniesBySlug($slug)
    {
        if (!$this->isAllowed('delete')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete companies')
            );
        }
        $company = $this->getCompanyBySlugName($slug);
        $this->companyMapper->remove($company);
    }

    /**
     * Return the company identified by $slugName
     *
     * @param \Company\Model\Company|null $slugName
     */
    public function getCompanyBySlugName($slugName)
    {
        return $this->companyMapper->findCompanyBySlugName($slugName);
    }

    /**
     * Returns a persistent category
     *
     * @param int $categoryId
     */
    public function getAllCategoriesById($categoryId)
    {
        if (!$this->isAllowed('edit')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit packages')
            );
        }

        return $this->categoryMapper->findAllCategoriesById($categoryId);
    }

    /**
     * Returns a persistent label
     *
     * @param int $labelId
     */
    public function getAllLabelsById($labelId)
    {
        if (!$this->isAllowed('edit')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit packages')
            );
        }

        return $this->labelMapper->findAllLabelsById($labelId);
    }

    /**
     * Returns a persistent package
     *
     * @param mixed $packageId
     */
    public function getEditablePackage($packageId)
    {
        if (!$this->isAllowed('edit')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit packages')
            );
        }
        if (is_null($packageId)) {
            throw new InvalidArgumentException('Invalid argument');
        }
        $package = $this->packageMapper->findEditablePackage($packageId);
        if (is_null($package)) {
            $package = $this->bannerPackageMapper->findEditablePackage($packageId);
        }
        if (is_null($package)) {
            $package = $this->featuredPackageMapper->findEditablePackage($packageId);
        }

        return $package;
    }

    /**
     * Returns all companies with a given $slugName and makes them persistent
     *
     * @param mixed $slugName
     */
    public function getEditableCompaniesBySlugName($slugName)
    {
        if (!$this->isAllowed('edit')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit companies')
            );
        }

        return $this->companyMapper->findEditableCompaniesBySlugName($slugName, true);
    }

    /**
     * Returns all jobs with a given slugname, owned by a company with
     * $companySlugName
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     */
    public function getEditableJobsByLanguageNeutralId($languageNeutralId)
    {
        if (!$this->isAllowed('edit')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit jobs')
            );
        }

        return $this->jobMapper->findJob(['languageNeutralId' => $languageNeutralId]);
    }

    /**
     * Get the Category Edit form.
     *
     * @return EditCategory For for editing JobCategories
     */
    public function getCategoryForm()
    {
        if (!$this->isAllowed('edit')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit categories')
            );
        }
        return $this->editCategoryForm;
    }

    /**
     * Get the Label Edit form.
     *
     * @return EditLabel Form for editing JobLabels
     */
    public function getLabelForm()
    {
        if (!$this->isAllowed('edit')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit labels')
            );
        }

        return $this->editLabelForm;
    }

    /**
     * Returns a the form for entering packages
     *
     * @return EditPackage Form
     */
    public function getPackageForm($type = 'job')
    {
        if ($type === 'banner') {
            return $this->editBannerPackageForm;
        }
        if ($type === 'featured') {
            return $this->editFeaturedPackageForm;
        }

        return $this->editPackageForm;
    }

    /**
     * Returns the form for entering jobs
     *
     * @return EditJob Job edit form
     */
    public function getJobForm()
    {
        if (!$this->isAllowed('edit')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit jobs')
            );
        }

        return $this->editJobForm;
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Get the default resource Id.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'company';
    }

    public static function getLanguageDescription($lang)
    {
        if ($lang === 'en') {
            return 'English';
        }
        return 'Dutch';
    }
}
