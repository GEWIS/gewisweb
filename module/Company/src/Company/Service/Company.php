<?php

namespace Company\Service;

//use Application\Service\AbstractService;
use Application\Service\AbstractAclService;
use Company\Model\ApprovalModel\ApprovalPending;
use Company\Model\ApprovalModel\ApprovalVacancy;
use Company\Model\Job as JobModel;
use Company\Model\JobCategory as CategoryModel;
use Company\Model\JobSector;
use Company\Model\JobSector as SectorModel;
use Company\Model\JobLabel as LabelModel;
use Company\Model\Job;
use Company\Model\JobLabelAssignment;
use Company\Model\ApprovalModel\ApprovalProfile;

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

    public function addBannerApproval($banner){

       $pending = new ApprovalPending();
       $pending->setType('banner');
       $pending->setBannerApproval($banner);
       $this->getApprovalMapper()->persist($pending);
       $this->getApprovalMapper()->save();

    }

    public function getCompanyIdentity() {
        $companyservice = $this->sm->get('company_auth_service');
        return $companyservice->getIdentity();
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

    private function getUniqueInArray($array, $callback)
    {
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
        $filteredCategories = $this->filterCategories($categories);
        $noVacancyCategory = count(array_filter($filteredCategories, function ($el) {
            return $el->getSlug() == "jobs";
        }));

        if (count($jobsWithoutCategory) > 0 && $noVacancyCategory == 0) {
            $filteredCategories[] = $this->getCategoryMapper()
                ->createNullCategory($translator->getLocale(), $translator);
        }

        return $filteredCategories;
    }

    /**
     * Returns all sectors for the given language
     *
     * @param $lang
     * @return array
     */
    public function getSectorList($lang)
    {
        $results = $this->getSectorMapper()->findAll();
        $sectors = [];
        foreach($results as $sector) {
            array_push($sectors, $this->getSectorMapper()->siblingSector($sector, $lang));
        }
        return $this->getUniqueInArray($sectors, function ($a) {
            return $a->getLanguageNeutralId();
        });

    }

    /**
     * Pick the vacancies that are visible as highlighted
     *
     * @param string $filter The current filter applied
     *
     * @return array Company\Model\JobCategory.
     */
    public function pickVacancies($category, $language){
        // Get all vacancyID's of highlighted vacancies
        $highlights = $this->getHighlightPackageMapper()->getHighlightedVacancies($category, $language);

        $highlightIDs = [];
        //$highlightNames = [];

        // If we already have at least 3 vacancies to highlight, we will pick a random selection of 3
        if (count($highlights)>=3) {
            $numbers = range(0, count($highlights)-1);
            shuffle($numbers);
            for ($x = 0; $x < 3; $x++){
                //array_push($highlightNames, $this->getJobMapper()->findJobById($highlights[$numbers[$x]][1])->getName());
                array_push($highlightIDs, $highlights[$numbers[$x]][1]);
            }
            //If we don't have 3 vacancies to highlight we will pick some random vacancies
        } else {
            // Update the array with vacancies first
            for ($x = 0; $x < count($highlights); $x++){
                //array_push($highlightNames, $this->getJobMapper()->findJobById($highlights[$x][1])->getName());
                array_push($highlightIDs, $highlights[$x][1]);
            }

            $needed = 3 - count($highlights);
            for ($needed; $needed > 0; $needed--) {
                // Randomly pick some new vacancies to highlight
                $extra = $this->getJobMapper()->getRandomVacancies($highlightIDs, $category, $language);
                // If there are no vacancies, we will show less than three
                If(count($extra)<1){
                    $highlights = [];
                    foreach ($highlightIDs as $id) {
                        array_push($highlights, $this->getJobMapper()->findJobById($id));
                    }
                    return $highlights;
                }
                $random = rand(0,count($extra)-1);
                //array_push($highlightNames, $this->getJobMapper()->findJobById($extra[$random]['id'])->getName());
                array_push($highlightIDs, $extra[$random]['id']);
            }
        }
//        print_r($highlightIDs);
        $highlights = [];
        foreach ($highlightIDs as $id) {
            array_push($highlights, $this->getJobMapper()->findJobById($id));
        }
        return $highlights;//$highlightNames;
    }

    /**
     * Returns all highlights for the given language
     *
     *
     * @return array
     */
    public function getHighlightsList($lang)
    {
        $highlightPackages = $this->getHighlightPackageMapper()->findAllActiveHighlights();
        $highlightIds = [];
        foreach($highlightPackages as $package) {
            $id = $package->getVacancy()->getLanguageNeutralId();
            $localeId = $this->getJobMapper()->siblingId($id, $lang);
            $highlightIds = array_merge($highlightIds, $localeId);
        }
        return $highlightIds;

    }

    /**
     * Returns all sectors for the given language
     *
     *
     * @return array
     */
    public function getHighlightsListAll($lang)
    {
        $highlightPackages = $this->getHighlightPackageMapper()->findAllActiveHighlightsList();
        foreach($highlightPackages as &$package) {
            $package['vacancy_name'] = $this->getJobMapper()->siblingJob($package['jobLnId'], $lang)->getName();
        }

        return $highlightPackages;

    }

    /**
     * Returns all vacancies for a company
     *
     *
     * @return array
     */
    public function getHighlightsForCompany($companyId, $lang)
    {
        $highlightPackages = [];
        $highlights = $this->getJobMapper()->findAllCompanyJobs($companyId);
        if (!is_null($highlights)){
            foreach ($highlights as $highlight) {
                $highlightPackages[$this->getJobMapper()->siblingID($highlight['languageNeutralId'], $lang)['id']] = $this->getJobMapper()->findJobById($this->getJobMapper()->siblingID($highlight['languageNeutralId'], $lang)['id'])->getName();
            }
        }
        return $highlightPackages;
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

        return $this->filterLabels($labels);
    }

    /**
     * Creates a new JobCategory.
     *
     * @param array $data Category data from the EditCategory form
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     * @throws \User\Permissions\NotAllowedException When a user is not allowed to create a job category
     *
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
     * Creates a new JobSector.
     *
     * @param array $data Sector data from the EditSector form
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     * @throws \User\Permissions\NotAllowedException When a user is not allowed to create a job category
     *
     */
    public function createSector($data)
    {
        if (!$this->isAllowed('insert')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to insert a job sector')
            );
        }

        $sectorDict = [];
        foreach ($this->getLanguages() as $lang) {
            $sector = new SectorModel();
            $sector->setLanguage($lang);
            $sectorDict[$lang] = $sector;
        }

        return $this->saveSectorData("", $sectorDict, $data);
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
     * Checks if the data is valid, and if it is, saves the JobSector
     *
     * @param int|string $languageNeutralId Identifier of the JobSectors to save
     * @param array $sectors The JobSectors to save
     * @param array $data The (new) data to save
     *
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     */
    public function saveSectorData($languageNeutralId, $sectors, $data)
    {
        if (!$this->isAllowed('edit')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit job categories')
            );
        }

        $sectorForm = $this->getSectorForm();
        $sectorForm->bind($sectors);
        $sectorForm->setData($data);

        if (!$sectorForm->isValid()) {
            return false;
        }

        $id = -1;
        foreach ($sectors as $sector) {
            $id = $this->setLanguageNeutralSectorId($id, $sector, $languageNeutralId);
            $this->getSectorMapper()->persist($sector);
            $this->saveSector();
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
     * Sets the languageNeutralId for this JobSector.
     *
     * @param int $id The id of the JobSector
     * @param JobSector $sector The JobSector
     * @param int|string $languageNeutralId The languageNeutralId of the JobSector
     *
     * @return int
     */
    private function setLanguageNeutralSectorId($id, $sector, $languageNeutralId)
    {
        if ($languageNeutralId == "") {
            $sector->setLanguageNeutralId($id);
            $this->getSectorMapper()->persist($sector);
            $this->saveSector();

            if ($id == -1) {
                $id = $sector->getId();
            }

            $sector->setLanguageNeutralId($id);
            return $id;
        }

        $sector->setLanguageNeutralId($languageNeutralId);

        return $id;
    }

    /**
     * Creates a new JobLabel.
     *
     * @param array $data Label data from the EditLabel form
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     * @throws \User\Permissions\NotAllowedException When a user is not allowed to create a job label
     *
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
        $type = $package->getType();
        if ($packageForm->isValid()) {
            if ($type === 'highlight') {
                $data['vacancy_id'] = $this->getJobMapper()->findJobById($data['vacancy_id']);
            }
            $package->exchangeArray($data);
            if ($type === 'banner') {
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
    public function saveCompanyByData($company, $data, $files, $logo_en = "", $logo_nl = "")
    {
        $companyForm = $this->getCompanyForm();

        $mergedData = array_merge_recursive(
            $data->toArray(),
            $files->toArray()
        );

        $companyForm->setData($mergedData);

        if ($companyForm->isValid()) {
            $company->exchangeArray($data);
            $company->setSector($this->getJobMapper()->findSectorsById($data['sector']));

            foreach ($company->getTranslations() as $translation) {
                $file = $files[$translation->getLanguage() . '_logo'];



                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        return false;
                    }
                    $oldPath = $translation->getLogo();
                    $newPath = $this->getFileStorageService()->storeUploadedFile($file);
                    //echo var_dump($newPath);




                    $translation->setLogo($newPath);
                    if ($oldPath !== '' && $oldPath != $newPath) {
                        $this->getFileStorageService()->removeFile($oldPath);
                    }
                }else{
                    if($logo_en != "" && $translation->getLanguage() === "en"){
                        $newPath = $logo_en;
                        $translation->setLogo($newPath);
                    }

                    if($logo_nl != "" && $translation->getLanguage() === "nl"){
                        $newPath = $logo_nl;
                        $translation->setLogo($newPath);
                    }



                }
            }
            $this->saveCompany();
            return true;
        }
    }

    public function saveCompanyApprovalByData($company, $data, $files, $logo_en = "", $logo_nl = "") {

        $profile = new ApprovalProfile();

        // when a company edits their profile, make sure the data they can't edit is maintained
        // fill in missing data using current database entries
        $data['name'] = $company->getName();
        $data['slugName'] = $company->getSlugName();
        $data['phone'] = $company->getPhone();
        $data['contactEmail'] = $company->getContactEmail();
        $data['highlightCredits'] = $company->getHighlightCredits();
        $data['bannerCredits'] = $company->getBannerCredits();
        $data['hidden'] = (int)$company->isHidden();
        $data['emailSubscription'] = (int)$company->getEmailSubscription();

        $companyForm = $this->getCompanyForm();
        $mergedData = array_merge_recursive(
            $data->toArray(),
            $files->toArray()
        );
        $companyForm->setData($mergedData);
//        print_r(var_dump($companyForm->isValid()));
//print_r($data);

        if ($companyForm->isValid()) {
            $profile = $this->getApprovalMapper()->insert($data['languages']);
            $profile->setSector($this->getJobMapper()->findSectorsById($data['sector']));
            $profile->setCompany($company);
            $profile->exchangeArray($data);
            $profile->setSector($this->getJobMapper()->findSectorsById($data['sector']));
            foreach ($profile->getTranslations() as $translation) {
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
                }else{
                    if($logo_en != "" && $translation->getLanguage() === "en"){
                        $newPath = $logo_en;
                        $translation->setLogo($newPath);
                    }

                    if($logo_nl != "" && $translation->getLanguage() === "nl"){
                        $newPath = $logo_nl;
                        $translation->setLogo($newPath);
                    }



                }




            }
            $pending = new ApprovalPending();
            $pending->setType('profile');
            $pending->setProfileApproval($profile);
            $this->getApprovalMapper()->persist($pending);

            $this->getApprovalMapper()->save($profile);
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
     * Saves all modified categories
     *
     */
    public function saveSector()
    {
        $this->getSectorMapper()->save();
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
            $companies = $this->insertCompany($data['languages']);
            $company = $companies[0];

            $company->exchangeArray($data);
            $company->setSector($this->getJobMapper()->findSectorsById($data['sector']));

            $newCompany = $companies[1];
            $newCompany->exchangeArray($data);
            foreach ($company->getTranslations() as $translation) {
                $file = $files[$translation->getLanguage() . '_logo'];

                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($file['error'] == UPLOAD_ERR_OK) {
                        $newPath = $this->getFileStorageService()->storeUploadedFile($file);
                        $translation->setLogo($newPath);
                    }
                }
            }
            $this->saveCompany();
            return $companies;
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
    public function insertPackageForCompanySlugNameByData($companySlugName, $data, $files, $type = "job", $isCompany = false)
    {
        $packageForm = $this->getPackageForm($type);
        $packageForm->setData($data);
        //$packageForm->setValidationGroup('vacancy_id');
        if ($packageForm->isValid()) {
            $package = $this->insertPackageForCompanySlugName($companySlugName, $type);
            if ($type === 'banner') {
                $newPath = $this->getFileStorageService()->storeUploadedFile($files);
                $package->setImage($newPath);
            }
            if ($type === 'highlight') {
                $data['vacancy_id'] = $this->getJobMapper()->findJobById($data['vacancy_id']);
            }
            $package->exchangeArray($data);
            $this->savePackage();

            if($isCompany && $type === "banner"){
                $this->addBannerApproval($package);
            }

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
     * @throws \Exception
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
        $this->setCentralJobData($jobs, $data);

        if (!$this->isAllowed('edit')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit jobs')
            );
        }
        foreach($jobs as $job) {
            $job->exchangeLanguageArray($data['jobs'][$job->getLanguage()]);
        }

        $jobForm = $this->getJobFormCompany();
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


            $id = $this->setLanguageNeutralJobId($id, $job, $languageNeutralId, $this->getJobMapper());
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
     * Sets the centralised fields in both language jobs.
     *
     * @param array $jobs The Job to save
     * @param array $data The (new) data to save
     */
    public function setCentralJobData($jobs, $data) {
        $x = 0;
        foreach ($jobs as $job) {
            $job->setEmail($data['email']);
            $job->setWebsite($data['website']);
            $job->setHours($data['hours']);

            $job->setSectors($this->getJobMapper()->findSectorsById($data['sectors'] + $x));
            $job->setCategory($this->getJobMapper()->findCategoryById($data['category'] +$x));
            $x++;

            $job->setLocation($data['location']);
            $job->setContactName($data['contactName']);
            $job->setPhone($data['phone']);
            if ($data['startingDate']!= null) {
                $job->setStartingDate(new \DateTime($data['startingDate']));
            }
        }
    }

    public function createJobApproval($packageId, $data, $files, $languageNeutralId) {
        $package = $this->getPackageMapper()->findPackage($packageId);
        $approvalJobs = [];

        foreach ($this->getLanguages() as $lang) {
            $approvalJob = new ApprovalVacancy();
            $approvalJob->setPackage($package);
            $approvalJob->setLanguage($lang);

            if($languageNeutralId != "") {
                $job = $this->getJobMapper()->siblingJob($languageNeutralId, $lang);
                $approvalJob->setVacancy($job);
            }

            $approvalJobs[$lang] = $approvalJob;
        }

        return $this->saveApprovalJobData($languageNeutralId, $approvalJobs, $data, $files);
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
    public function saveApprovalJobData($languageNeutralId, $approvalJobs, $data, $files)
    {
        $this->setCentralJobData($approvalJobs, $data);

        if (!$this->isAllowed('edit')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit jobs')
            );
        }

        $jobForm = $this->getJobFormCompany();
        $mergedData = array_merge_recursive(
            $data->toArray(),
            $files->toArray()
        );

        // check if this works
        $jobForm->setCompanySlug(current($approvalJobs)->getCompany()->getSlugName());
        $jobForm->setCurrentSlug($data['slugName']);
        $jobForm->bind($approvalJobs);
        $jobForm->setData($mergedData);

        if (!$jobForm->isValid()) {
            return false;
        }
        $id = -1;

        $labelIds = $data['labels'];
        if (is_null($labelIds)) {
            $labelIds = [];
        }

        foreach ($approvalJobs as $lang => $job) {
            $file = $files['jobs'][$lang]['attachment_file'];

            if ($file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $oldPath = $job->getAttachment();

                try {
                    $newPath = $this->getFileStorageService()->storeUploadedFile($file);
                } catch (\Exception $e) {
                    return false;
                }

                // check if this needs to be changed
                if (!is_null($oldPath) && $oldPath != $newPath) {
                    $this->getFileStorageService()->removeFile($oldPath);
                }

                $job->setAttachment($newPath);
            }

            $job->setTimeStamp(new \DateTime());
            $id = $this->setLanguageNeutralJobId($id, $job, $languageNeutralId, $this->getApprovalMapper());
            $this->getApprovalMapper()->persist($job);

            $pending = new ApprovalPending();
            $pending->setType('vacancy');
            $pending->setVacancyApproval($job);
            $this->getApprovalMapper()->persist($pending);

            $this->getApprovalMapper()->save();

//            $mapper = $this->getLabelMapper();
//            $lang = $job->getLanguage();
//            // Contains language specific labels
//            $labelsLangBased = [];
//            foreach ($labelIds as $labelId) {
//                $label = $mapper->findLabelById($labelId);
//                $labelsLangBased[] = $mapper->siblingLabel($label, $lang)->getId();
//            }
//            $this->setLabelsForJob($job, $labelsLangBased);
        }

        return true;
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

    private function setLanguageNeutralJobId($id, $job, $languageNeutralId, $mapper)
    {
        if ($languageNeutralId == "") {
            $job->setLanguageNeutralId($id);
            $mapper->persist($job);
            $mapper->save($job);

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

        return $this->getJobMapper()->insertIntoPackage($package, $lang, $languageNeutralId);
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
        $this>$this->getHighlightPackageMapper()->delete($packageId);
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
     * Returns a persistent sector
     *
     * @param int $sectorId
     */
    public function getAllSectorsById($sectorId)
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit packages')
            );
        }

        return $this->getSectorMapper()->findAllSectorsById($sectorId);
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
            throw new \InvalidArgumentException('Invalid argument');
        }
        $package = $this->getPackageMapper()->findEditablePackage($packageId);
        if (is_null($package)) {
            $package = $this->getBannerPackageMapper()->findEditablePackage($packageId);
        }
        if (is_null($package)) {
            $package = $this->getFeaturedPackageMapper()->findEditablePackage($packageId);
        }
        if (is_null($package)) {
            $package = $this->getHighlightPackageMapper()->findEditablePackage($packageId);
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

        return $this->getJobMapper()->findJob(['languageNeutralId' => $languageNeutralId]);
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
            }
            return $jobs;
        }
        $locale = $translator->getLocale();
        $dict["language"] = $locale;
        $jobs = $this->getJobMapper()->findJob($dict);

        return $jobs;
    }

    public function getAllJobs() {
        $jobList = $this->getJobMapper()->findAllActiveJobs($this->getTranslator()->getLocale());

        $array = [];
        foreach ($jobList as $job) {
            if ($job->isActive()) {
                $array[] = $job;
            }
        }

        return $array;
    }

    /**
     *
     *
     * @param integer $companyId the id of the company who's
     * categories will be fetched.
     * @param string $locale The current language of the website
     *
     * @return array Company\Model\JobCategory.
     */
    public function getHighlightableVacancies($companyId, $locale){
        return $this->getJobMapper()->findHighlightableVacancies(
            $companyId,
            $this->getHighlightPackageMapper()->findHighlightedCategories($companyId),
            $locale);
    }

    /**
     *
     *
     * @param integer $companyId the id of the company who's
     * categories will be fetched.
     * @param string $locale The current language of the website
     * @param int the languageNeutralId of the category in which the currently highlighted vacancy lies
     *
     * @return array Company\Model\JobCategory.
     */
    public function getEditHighlightableVacancies($companyId, $locale, $currentCategory){
        $highlightedCategories = $this->getHighlightPackageMapper()->findHighlightedCategories($companyId);

        //Remove the current category from this list, such that vacancies from this category can be chosen
        foreach ($highlightedCategories as &$highlightedCategory) {
            if (($key = array_search($currentCategory, $highlightedCategory)) !== false) {
                unset($highlightedCategory[$key]);
            }
        }

        return $this->getJobMapper()->findHighlightableVacancies(
            $companyId,
            $highlightedCategories,
            $locale);
    }

    /**
     * Gets an array with the names from all vacancies in a vacancy object
     * where the location in the array is the vacancy id
     *
     *
     */
    public function getEditVacancyNames($companyId, $currentCategory) {
        //Get current language
        $locale = $this->getTranslator()->getLocale();

        $vacancy_objects = $this->getEditHighlightableVacancies($companyId, $locale, $currentCategory);

        $vacancyNames = [];

        foreach ($vacancy_objects as &$vacancy) {
            $vacancyNames[$vacancy->getId()] = $vacancy->getName();
        }
        return $vacancyNames;
    }

    /**
     * Gets an array with the names from all vacancies in a vacancy object
     * where the location in the array is the vacancy id
     *
     *
     */
    public function getVacancyNames($companyId) {
        //Get current language
        $locale = $this->getTranslator()->getLocale();

        $vacancy_objects = $this->getHighlightableVacancies($companyId, $locale);

        $vacancyNames = [];

        foreach ($vacancy_objects as &$vacancy) {
            $vacancyNames[$vacancy->getId()] = $vacancy->getName();
        }
        return $vacancyNames;
    }

    /**
     * Get the active highlights a company has
     *
     * @param integer $companyId the id of the company who's
     * number of highlights will be fetched.
     * @param string $lang the language in which the vacancies should be fetched
     *
     * @return array The names and the expiration dates of the active highlights of a company
     */
    public function getCurrentHighlights($companyId, $lang) {
        //Get the vacancy ids and the expiration dates of the highlights in the companyPackage table
        $highlights = $this->getHighlightPackageMapper()->findCurrentHighlights($companyId);
        $temp = [];
        $correctHighlights = [];

        foreach ($highlights as $highlight) {
            //Get the correct vacancy id based on language
            $vacancyId = $this->getJobMapper()->siblingId($highlight['languageNeutralId'], $lang)['id'];
            //Get the name of the vacancy in the correct language
            $temp['name'] = $this->getJobMapper()->findJobById($vacancyId)->getName();
            $temp['expires'] = $highlight['expires'];
            $temp['id'] = $highlight['id'];

            array_push($correctHighlights, $temp);
        }
        return $correctHighlights;
    }

    /**
     * Get the number of highlights a company has
     *
     * @param integer $companyId the id of the company who's
     * number of highlights will be fetched.
     *
     * @return int number of highlights for a company
     */
    public function getNumberOfHighlightsPerCompany($companyId) {
        return $this->getHighlightPackageMapper()->findNumberOfHighlightsPerCompany($companyId);
    }

    /**
     * Get the number of highlights in a category
     *
     * @param integer $vacancyId the id of the vacancy who's
     * number of highlights will be fetched.
     *
     * @return int number of highlights in a category
     */
    public function getNumberOfHighlightsPerCategory($vacancyId) {
        $categoryId = $this->getJobMapper()->findJobById($vacancyId)->getCategory();
        return $this->getHighlightPackageMapper()->findNumberOfHighlightsPerCategory($categoryId);
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
     * @return EditCategory Form for editing JobCategories
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
     * Get the Sector Edit form.
     *
     * @return EditSector Form for editing JobSectors
     */
    public function getSectorForm()
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit sectors')
            );
        }
        return $this->sm->get('company_admin_edit_sector_form');
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
        if ($type === 'highlight') {
            return $this->sm->get('company_admin_edit_highlightpackage_form');
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


    public function getJobFormCompany()
    {
        if (!$this->isAllowed('edit')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to edit jobs')
            );
        }

        return $this->sm->get('company_edit_job_form');
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
     * Returns the packageMapper
     *
     */
    public function getHighlightPackageMapper()
    {
        return $this->sm->get('company_mapper_highlightpackage');
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
     * Returns the sector mapper
     *
     */
    public function getSectorMapper()
    {
        return $this->sm->get('company_mapper_sector');
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


    /**
     * Returns the approval mapper
     *
     */
    public function getApprovalMapper()
    {
        return $this->sm->get('company_mapper_approval');
    }


    public function getLanguageDescription($lang)
    {
        if ($lang === 'en') {
            return 'English';
        }
        return 'Dutch';
    }
}
