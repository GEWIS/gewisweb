<?php

namespace Company\Service;

//use Application\Service\AbstractService;
use Application\Service\AbstractAclService;
use Company\Model\Job as JobModel;
use Company\Model\JobCategory as CategoryModel;
use Company\Model\JobLabel as LabelModel;
use Company\Model\Job;
use Company\Model\JobLabelAssignment;

/**
 * Company service.
 */
class Company extends AbstractACLService
{
    /**
     * Returns a random banner for display on the frontpage
     *
     */
    public function getCurrentBanner()
    {
        $translator = $this->getTranslator();
        if (!$this->isAllowed('showBanner')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the banner')
            );
        }
        return $this->getBannerPackageMapper()->getBannerPackage();
    }

    public function getFeaturedPackage()
    {
        $translator = $this->getTranslator();
        if (!$this->isAllowed('viewFeaturedCompany')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the featured company')
            );
        }
        return $this->getFeaturedPackageMapper()->getFeaturedPackage($translator->getLocale());
    }

    private function getFuturePackageStartsBeforeDate($date)
    {
        $startPackages = array_merge(
            $this->getPackageMapper()->findFuturePackageStartsBeforeDate($date),
            $this->getBannerPackageMapper()->findFuturePackageStartsBeforeDate($date),
            $this->getFeaturedPackageMapper()->findFuturePackageStartsBeforeDate($date)
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
            $this->getPackageMapper()->findFuturePackageExpirationsBeforeDate($date),
            $this->getBannerPackageMapper()->findFuturePackageExpirationsBeforeDate($date),
            $this->getFeaturedPackageMapper()->findFuturePackageExpirationsBeforeDate($date)
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
        $translator = $this->getTranslator();
        if (!$this->isAllowed('listall')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to list the companies')
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
        $translator = $this->getTranslator();
        if (!$this->isAllowed('list')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to list the companies')
            );
        }
        return $this->getCompanyMapper()->findPublicByLocale($translator->getLocale());
    }
    // Company list for admin interface
    /**
     * Returns a list of all companies (including hidden companies)
     *
     */
    public function getHiddenCompanyList()
    {
        if (!$this->isAllowed('listall')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to access the admin interface')
            );
        }
        return $this->getCompanyMapper()->findAll();
    }

    public function categoryForSlug($slug)
    {
        $translator = $this->getTranslator();
        $mapper = $this->getCategoryMapper();
        $category = $mapper->findCategory($slug);
        $locale = $translator->getLocale();

        if ($category === null && $slug == "jobs") {
            $category = $mapper->createNullCategory($translator->getLocale(), $translator);
        }
        if ($category === null || $category->getLanguage() == $locale) {
            return $category;
        }
        $category = $mapper->siblingCategory($category, $locale);
        return $category;
    }

    /**
     * Filters out categories that are not used in active jobs
     *
     * @param array $categories
     * @return array
     */
    private function filterCategories($categories)
    {
        $nonemptyCategories = [];
        foreach ($categories as $category) {
            if (count($this->getActiveJobList(['jobCategoryId' => $category->getId()])) > 0) {
                $nonemptyCategories[] = $category;
            }
        }
        return $nonemptyCategories;
    }

    /**
     * Filters out labels that are not used in active jobs
     *
     * @param array $labels
     * @return array
     */
    private function filterLabels($labels)
    {
        $nonemptyLabels = [];
        foreach ($labels as $label) {
            if (count($this->getActiveJobList(['jobCategoryId' => $label->getId()])) > 0) {
                $nonemptyLabels[] = $label;
            }
        }
        return $nonemptyLabels;
    }

    private function getUniqueInArray($array, $callback) {
        $tempResults = [];
        $resultArray = [];
        foreach ($array as $x) {
            $newVar = $callback($x);
            if (!array_key_exists($newVar, $tempResults)) {
                $resultArray[] = $x;
                $tempResults[$newVar] = $x;
            }
        }
        return $resultArray;
    }

    /**
     * Returns all categories if $visible is false, only returns visible categories if $visible is false
     *
     * @param $visible
     * @return array
     */
    public function getCategoryList($visible)
    {
        $translator = $this->getTranslator();
        if (!$visible) {
            if (!$this->isAllowed('listAllCategories')) {
                throw new \User\Permissions\NotAllowedException(
                    $translator->translate('You are not allowed to access the admin interface')
                );
            }
            $results = $this->getCategoryMapper()->findAll();
            return $this->getUniqueInArray($results, function ($a) {
                return $a->getLanguageNeutralId();
            });
        }
        if (!$this->isAllowed('listVisibleCategories')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to list all categories')
            );
        }
        $categories = $this->getCategoryMapper()->findVisibleCategoryByLanguage($translator->getLocale());
        $jobsWithoutCategory = $this->getJobMapper()->findJobsWithoutCategory($translator->getLocale());
        $filteredCategories =  $this->filterCategories($categories);
        $noVacancyCategory = count(array_filter($filteredCategories, function ($el) {
            return $el->getSlug() == "jobs";
        })) ;
        if (count($jobsWithoutCategory) > 0 && $noVacancyCategory  == 0) {
            $filteredCategories[] = $this->getCategoryMapper()
                ->createNullCategory($translator->getLocale(), $translator);
        }
        return $filteredCategories;
    }

    /**
     * Returns all labels if $visible is false, only returns visible labels if $visible is false
     *
     * @param $visible
     * @return array
     */
    public function getLabelList($visible)
    {
        $translator = $this->getTranslator();
        if (!$visible) {
            if (!$this->isAllowed('listAllLabels')) {
                throw new \User\Permissions\NotAllowedException(
                    $translator->translate('You are not allowed to access the admin interface')
                );
            }
            $results = $this->getLabelMapper()->findAll();
            return $this->getUniqueInArray($results, function ($a) {
                return $a->getLanguageNeutralId();
            });
        }
        if (!$this->isAllowed('listVisibleLabels')) {
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to list all labels')
            );
        }
        $labels = $this->getLabelMapper()->findVisibleLabelByLanguage($translator->getLocale());
        $filteredLabels =  $this->filterLabels($labels);
        return $filteredLabels;
    }

    /**
     * Creates a new JobCategory.
     *
     * @param array $data Category data from the EditCategory form
     * @throws \User\Permissions\NotAllowedException When a user is not allowed to create a job category
     *
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     */
    public function createCategory($data)
    {
        if (!$this->isAllowed('insert')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to insert a job category')
            );
        }

        $categoryDict = [];
        foreach ($this->getLanguages() as $lang) {
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
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit job categories')
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
            $this->getCategoryMapper()->persist($category);
            $this->saveCategory();
        }

        return (($languageNeutralId == "") ? $id : $languageNeutralId);
    }

    /**
     * Sets the languageNeutralId for this JobCategory.
     *
     * @param int $id The id of the JobCategory
     * @param JobCategory $category The JobCategory
     * @param int|string $languageNeutralId The languageNeutralId of the JobCategory
     *
     * @return int
     */
    private function setLanguageNeutralCategoryId($id, $category, $languageNeutralId)
    {
        if ($languageNeutralId == "") {
            $category->setLanguageNeutralId($id);
            $this->getCategoryMapper()->persist($category);
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
     * @throws \User\Permissions\NotAllowedException When a user is not allowed to create a job label
     *
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     */
    public function createLabel($data)
    {
        if (!$this->isAllowed('insert')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to insert a job label')
            );
        }

        $labelDict = [];
        foreach ($this->getLanguages() as $lang) {
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
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit job labels')
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
            $this->getLabelMapper()->persist($label);
            $this->saveLabel();
        }

        return (($languageNeutralId == "") ? $id : $languageNeutralId);
    }

    /**
     * Sets the languageNeutralId for this JobLabel.
     *
     * @param int $id The id of the JobLabel
     * @param JobLabel $label The JobLabel
     * @param int|string $languageNeutralId The languageNeutralId of the JobLabel
     *
     * @return int
     */
    private function setLanguageNeutralLabelId($id, $label, $languageNeutralId)
    {
        if ($languageNeutralId == "") {
            $label->setLanguageNeutralId($id);
            $this->getLabelMapper()->persist($label);
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
                    $newPath = $this->getFileStorageService()->storeUploadedFile($file);
                    $package->setImage($newPath);
                    if ($oldPath != '' && $oldPath != $newPath) {
                        $this->getFileStorageService()->removeFile($oldPath);
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
     * @param mixed $company
     * @param mixed $data
     */
    public function saveCompanyByData($company, $data, $files)
    {
        $companyForm = $this->getCompanyForm();
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
                    $newPath = $this->getFileStorageService()->storeUploadedFile($file);
                    $translation->setLogo($newPath);
                    if ($oldPath !== '' && $oldPath != $newPath) {
                        $this->getFileStorageService()->removeFile($oldPath);
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
        $this->getCategoryMapper()->save();
    }

    /**
     * Saves all modified labels
     *
     */
    public function saveLabel()
    {
        $this->getLabelMapper()->save();
    }

    /**
     * Saves all modified jobs
     *
     */
    public function saveJob()
    {
        $this->getJobMapper()->save();
    }

    /**
     * Saves all modified companies
     *
     */
    public function saveCompany()
    {
        $this->getCompanyMapper()->save();
    }

    /**
     * Saves all modified packages
     *
     */
    public function savePackage()
    {
        $this->getPackageMapper()->save();
    }

    /**
     * Checks if the data is valid, and if it is, inserts the company, and sets
     * all data
     *
     * @param mixed $data
     */
    public function insertCompanyByData($data, $files)
    {
        $companyForm = $this->getCompanyForm();
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
                    $newPath = $this->getFileStorageService()->storeUploadedFile($file);
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
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a company')
            );
        }
        return $this->getCompanyMapper()->insert($languages);
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
                $newPath = $this->getFileStorageService()->storeUploadedFile($files);
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
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a package')
            );
        }
        $companies = $this->getEditableCompaniesBySlugName($companySlugName);
        $company = $companies[0];
        return $this->getPackageMapper()->insertPackageIntoCompany($company, $type);
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
        $package = $this->getPackageMapper()->findPackage($packageId);
        $jobs = [];
        foreach ($this->getLanguages() as $lang) {
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
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit jobs')
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
                    $newPath = $this->getFileStorageService()->storeUploadedFile($file);
                } catch (\Exception $e) {
                    return false;
                }

                if (!is_null($oldPath) && $oldPath != $newPath) {
                    $this->getFileStorageService()->removeFile($oldPath);
                }

                $job->setAttachment($newPath);
            }

            $job->setTimeStamp(new \DateTime());
            $id = $this->setLanguageNeutralJobId($id, $job, $languageNeutralId);
            $this->getJobMapper()->persist($job);
            $this->saveJob();

            $mapper = $this->getLabelMapper();
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
        $mapper = $this->getLabelAssignmentMapper();
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
        $mapperLabel = $this->getLabelMapper();
        $mapperLabelAssignment = $this->getLabelAssignmentMapper();
        $mapperJob = $this->getJobMapper();
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
        $mapper = $this->getLabelAssignmentMapper();
        foreach ($labels as $label) {
            $toRemove = $mapper->findAssignmentByJobIdAndLabelId($job->getId(), $label);
            $mapper->delete($toRemove);
        }
    }

    private function setLanguageNeutralJobId($id, $job, $languageNeutralId)
    {
        if ($languageNeutralId == "") {
            $job->setLanguageNeutralId($id);
            $this->getJobMapper()->persist($job);
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
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to insert a job')
            );
        }
        $package = $this->getEditablePackage($packageId);
        $result = $this->getJobMapper()->insertIntoPackage($package, $lang, $languageNeutralId);

        return $result;
    }

    /**
     * Deletes the given package
     *
     * @param mixed $packageId
     */
    public function deletePackage($packageId)
    {
        if (!$this->isAllowed('delete')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete packages')
            );
        }
        $this->getPackageMapper()->delete($packageId);
        $this->getBannerPackageMapper()->delete($packageId);
    }

    /**
     * Deletes the given job
     *
     * @param mixed $packageId
     */
    public function deleteJob($jobId)
    {
        if (!$this->isAllowed('delete')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete jobs')
            );
        }
        $this->getJobMapper()->deleteByLanguageNeutralId($jobId);
    }

    /**
     * Deletes the company identified with $slug
     *
     * @param mixed $slug
     */
    public function deleteCompaniesBySlug($slug)
    {
        if (!$this->isAllowed('delete')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete companies')
            );
        }
        $company = $this->getCompanyBySlugName($slug);
        $this->getCompanyMapper()->remove($company);
    }

    /**
     * Return the company identified by $slugName
     *
     * @param \Company\Model\Company|null $slugName
     */
    public function getCompanyBySlugName($slugName)
    {
        return $this->getCompanyMapper()->findCompanyBySlugName($slugName);
    }

    /**
     * Returns a persistent category
     *
     * @param int $categoryId
     */
    public function getAllCategoriesById($categoryId)
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit packages')
            );
        }

        return $this->getCategoryMapper()->findAllCategoriesById($categoryId);
    }


    /**
     * Returns a persistent label
     *
     * @param int $labelId
     */
    public function getAllLabelsById($labelId)
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit packages')
            );
        }

        return $this->getLabelMapper()->findAllLabelsById($labelId);
    }

    /**
     * Returns a persistent package
     *
     * @param mixed $packageId
     */
    public function getEditablePackage($packageId)
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit packages')
            );
        }
        if (is_null($packageId)) {
            throw new \Exception('Invalid argument');
        }
        $package = $this->getPackageMapper()->findEditablePackage($packageId);
        if (is_null($package)) {
            $package = $this->getBannerPackageMapper()->findEditablePackage($packageId);
        }
        if (is_null($package)) {
            $package = $this->getFeaturedPackageMapper()->findEditablePackage($packageId);
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
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit companies')
            );
        }
        return $this->getCompanyMapper()->findEditableCompaniesBySlugName($slugName, true);
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
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit jobs')
            );
        }
        $res = $this->getJobMapper()->findJob(['languageNeutralId' => $languageNeutralId]);
        return $res;
    }

    /**
     * Returns all jobs with a $jobSlugName, owned by a company with a
     * $companySlugName, and a specific $category
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     * @param mixed $category
     */
    public function getJobs($dict)
    {
        $translator = $this->getTranslator();
        if (array_key_exists("jobCategory", $dict) && $dict["jobCategory"] === null) {
            $jobs = $this->getJobMapper()->findJobsWithoutCategory($translator->getLocale());
            foreach ($jobs as $job) {
                $job->setCategory($this->getCategoryMapper()
                    ->createNullCategory($translator->getLocale(), $translator));

                // TODO: This is a hotfix for some ORM issues:
                $job->setLabels($this->getLabelAssignmentMapper()->findAssignmentsByJobId($job->getId()));
            }
            return $jobs;
        }
        $locale = $translator->getLocale();
        $dict["language"] = $locale;
        $jobs = $this->getJobMapper()->findJob($dict);
        foreach ($jobs as $job) {
            // TODO: This is a hotfix for some ORM issues:
            $job->setLabels($this->getLabelAssignmentMapper()->findAssignmentsByJobId($job->getId()));
        }
        return $jobs;
    }

    /**
     * Get the Company Edit form.
     *
     * @return Company Edit form
     */
    public function getCompanyForm()
    {
        return $this->sm->get('company_admin_edit_company_form');
    }

    /**
     * Get the Category Edit form.
     *
     * @return EditCategory For for editing JobCategories
     */
    public function getCategoryForm()
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit categories')
            );
        }
        return $this->sm->get('company_admin_edit_category_form');
    }

    /**
     * Get the Label Edit form.
     *
     * @return EditLabel Form for editing JobLabels
     */
    public function getLabelForm()
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit labels')
            );
        }
        return $this->sm->get('company_admin_edit_label_form');
    }

    /**
     * Returns a the form for entering packages
     *
     */
    public function getPackageForm($type = 'job')
    {
        if ($type === 'banner') {
            return $this->sm->get('company_admin_edit_bannerpackage_form');
        }
        if ($type === 'featured') {
            return $this->sm->get('company_admin_edit_featuredpackage_form');

        }
        return $this->sm->get('company_admin_edit_package_form');
    }

    /**
     * Returns the form for entering jobs
     *
     * @return EditJob Job edit form
     */
    public function getJobForm()
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit jobs')
            );
        }
        return $this->sm->get('company_admin_edit_job_form');
    }

    /**
     * Returns all jobs that are active
     *
     */
    public function getActiveJobList($dict = [])
    {
        $jobList = $this->getJobs($dict);
        $array = [];
        foreach ($jobList as $job) {
            if ($job->isActive()) {
                $array[] = $job;
            }
        }

        return $array;
    }

    /**
     * Returns the companyMapper
     *
     */
    private function getCompanyMapper()
    {
        return $this->sm->get('company_mapper_company');
    }

    /**
     * Returns the packageMapper
     *
     */
    private function getPackageMapper()
    {
        return $this->sm->get('company_mapper_package');
    }

    /**
     * Returns the packageMapper
     *
     */
    private function getBannerPackageMapper()
    {
        return $this->sm->get('company_mapper_bannerpackage');
    }

    /**
     * Returns the packageMapper
     *
     */
    public function getFeaturedPackageMapper()
    {
        return $this->sm->get('company_mapper_featuredpackage');
    }

    /**
     * Returns the jobMapper
     *
     */
    public function getJobMapper()
    {
        return $this->sm->get('company_mapper_job');
    }

    /**
     * Returns the category mapper
     *
     */
    public function getCategoryMapper()
    {
        return $this->sm->get('company_mapper_category');
    }

    /**
     * Returns the label mapper
     *
     */
    public function getLabelMapper()
    {
        return $this->sm->get('company_mapper_label');
    }

    /**
     * Returns the label assignment mapper
     *
     */
    public function getLabelAssignmentMapper()
    {
        return $this->sm->get('company_mapper_label_assignment');
    }

    /**
     * Get the Acl.
     *
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('company_acl');
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
     * Gets the storage service.
     *
     * @return \Application\Service\Storage
     */
    public function getLanguages()
    {
        return $this->sm->get('application_get_languages');
    }
    public function getLanguageDescription($lang)
    {
        if ($lang === 'en') {
            return 'English';
        }
        return 'Dutch';
    }
}
