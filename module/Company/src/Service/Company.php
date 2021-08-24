<?php

namespace Company\Service;

use Application\Service\FileStorage;
use Doctrine\ORM\ORMException;
use Company\Form\{
    EditCategory as EditCategoryForm,
    EditCompany as EditCompanyForm,
    EditJob as EditJobForm,
    EditLabel as EditLabelForm,
    EditPackage as EditPackageForm,
};
use Company\Mapper\{
    BannerPackage as BannerPackageMapper,
    Category as CategoryMapper,
    Company as CompanyMapper,
    FeaturedPackage as FeaturedPackageMapper,
    Job as JobMapper,
    Label as LabelMapper,
    LabelAssignment as LabelAssignmentMapper,
    Package as PackageMapper,
};
use Company\Model\{
    Company as CompanyModel,
    Job as JobModel,
    JobCategory as JobCategoryModel,
    JobLabel as JobLabelModel,
    JobLabelAssignment as JobLabelAssignmentModel,
};
use DateTime;
use Exception;
use InvalidArgumentException;
use Laminas\Mvc\I18n\Translator;
use Laminas\Stdlib\Parameters;
use User\Permissions\NotAllowedException;

/**
 * Company service.
 */
class Company
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var FileStorage
     */
    private FileStorage $storageService;

    /**
     * @var CompanyMapper
     */
    private CompanyMapper $companyMapper;

    /**
     * @var PackageMapper
     */
    private PackageMapper $packageMapper;

    /**
     * @var BannerPackageMapper
     */
    private BannerPackageMapper $bannerPackageMapper;

    /**
     * @var FeaturedPackageMapper
     */
    private FeaturedPackageMapper $featuredPackageMapper;

    /**
     * @var JobMapper
     */
    private JobMapper $jobMapper;

    /**
     * @var CategoryMapper
     */
    private CategoryMapper $categoryMapper;

    /**
     * @var LabelMapper
     */
    private LabelMapper $labelMapper;

    /**
     * @var LabelAssignmentMapper
     */
    private LabelAssignmentMapper $labelAssignmentMapper;

    /**
     * @var EditCompanyForm
     */
    private EditCompanyForm $editCompanyForm;

    /**
     * @var EditPackageForm
     */
    private EditPackageForm $editPackageForm;

    /**
     * @var EditPackageForm
     */
    private EditPackageForm $editBannerPackageForm;

    /**
     * @var EditPackageForm
     */
    private EditPackageForm $editFeaturedPackageForm;

    /**
     * @var EditJobForm
     */
    private EditJobForm $editJobForm;

    /**
     * @var EditCategoryForm
     */
    private EditCategoryForm $editCategoryForm;

    /**
     * @var EditLabelForm
     */
    private EditLabelForm $editLabelForm;

    /**
     * @var array
     */
    private array $languages;

    /**
     * @var AclService
     */
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        FileStorage $storageService,
        CompanyMapper $companyMapper,
        PackageMapper $packageMapper,
        BannerPackageMapper $bannerPackageMapper,
        FeaturedPackageMapper $featuredPackageMapper,
        JobMapper $jobMapper,
        CategoryMapper $categoryMapper,
        LabelMapper $labelMapper,
        LabelAssignmentMapper $labelAssignmentMapper,
        EditCompanyForm $editCompanyForm,
        EditPackageForm $editPackageForm,
        EditPackageForm $editBannerPackageForm,
        EditPackageForm $editFeaturedPackageForm,
        EditJobForm $editJobForm,
        EditCategoryForm $editCategoryForm,
        EditLabelForm $editLabelForm,
        array $languages,
        AclService $aclService
    ) {
        $this->translator = $translator;
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
        $this->aclService = $aclService;
    }

    /**
     * Returns a random banner for display on the frontpage.
     */
    public function getCurrentBanner()
    {
        if (!$this->aclService->isAllowed('showBanner', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the banner'));
        }

        return $this->bannerPackageMapper->getBannerPackage();
    }

    public function getFeaturedPackage()
    {
        if (!$this->aclService->isAllowed('viewFeaturedCompany', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the featured company')
            );
        }

        return $this->featuredPackageMapper->getFeaturedPackage($this->translator->getTranslator()->getLocale());
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
     * Searches for packages that change before $date.
     *
     * @param DateTime $date The date until where to search
     *
     * @return array Two sorted arrays, containing the packages that respectively start and expire between now and $date,
     */
    public function getPackageChangeEvents($date)
    {
        if (!$this->aclService->isAllowed('listall', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list the companies'));
        }

        $startPackages = $this->getFuturePackageStartsBeforeDate($date);
        $expirePackages = $this->getFuturePackageExpiresBeforeDate($date);

        return [$startPackages, $expirePackages];
    }

    /**
     * Returns an list of all companies (excluding hidden companies).
     */
    public function getCompanyList()
    {
        if (!$this->aclService->isAllowed('list', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list the companies'));
        }

        return $this->companyMapper->findPublicByLocale($this->translator->getTranslator()->getLocale());
    }

    // Company list for admin interface

    /**
     * Returns a list of all companies (including hidden companies).
     */
    public function getHiddenCompanyList()
    {
        if (!$this->aclService->isAllowed('listall', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to access the admin interface')
            );
        }

        return $this->companyMapper->findAll();
    }

    /**
     * Get public company by id.
     *
     * @param int $id
     *
     * @return CompanyModel|null
     */
    public function getCompanyById($id)
    {
        if (!$this->aclService->isAllowed('listall', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list the companies'));
        }

        return $this->companyMapper->find($id);
    }

    public function categoryForSlug($slug)
    {
        $mapper = $this->categoryMapper;
        $category = $mapper->findCategory($slug);
        $locale = $this->translator->getTranslator()->getLocale();

        if (null === $category && 'jobs' == $slug) {
            $category = $mapper->createNullCategory($this->translator->getTranslator()->getLocale(), $this->translator);
        }
        if (null === $category || $category->getLanguage() == $locale) {
            return $category;
        }

        return $mapper->siblingCategory($category, $locale);
    }

    /**
     * Creates a new JobCategoryModel.
     *
     * @param Parameters $data Category data from the EditCategory form
     *
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     *
     * @throws NotAllowedException When a user is not allowed to create a job category
     */
    public function createCategory(Parameters $data)
    {
        if (!$this->aclService->isAllowed('insert', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to insert a job category'));
        }

        $categoryDict = [];
        foreach ($this->languages as $lang) {
            $category = new JobCategoryModel();
            $category->setLanguage($lang);
            $categoryDict[$lang] = $category;
        }

        return $this->saveCategoryData(null, $categoryDict, $data);
    }

    /**
     * Checks if the data is valid, and if it is, saves the JobCategoryModel.
     *
     * @param int|null $languageNeutralId Identifier of the JobCategories to save
     * @param array $categories The JobCategories to save
     * @param Parameters $data The (new) data to save
     *
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     */
    public function saveCategoryData(?int $languageNeutralId, array $categories, Parameters $data)
    {
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

        return (null === $languageNeutralId) ? $id : $languageNeutralId;
    }

    /**
     * Sets the languageNeutralId for this JobCategoryModel.
     *
     * @param int $id The id of the JobCategoryModel
     * @param JobCategoryModel $category The JobCategoryModel
     * @param int|null $languageNeutralId The languageNeutralId of the JobCategoryModel
     *
     * @return int
     */
    private function setLanguageNeutralCategoryId(int $id, JobCategoryModel $category, ?int $languageNeutralId): int
    {
        if (null === $languageNeutralId) {
            $category->setLanguageNeutralId($id);
            $this->categoryMapper->persist($category);
            $this->saveCategory();

            if (-1 === $id) {
                $id = $category->getId();
            }

            $category->setLanguageNeutralId($id);

            return $id;
        }

        $category->setLanguageNeutralId($languageNeutralId);

        return $id;
    }

    /**
     * Creates a new JobLabelModel.
     *
     * @param Parameters $data Label data from the EditLabel form
     *
     * @return bool|int Returns false on failure, and the languageNeutralId on success
     *
     * @throws NotAllowedException When a user is not allowed to create a job label
     */
    public function createLabel(Parameters $data)
    {
        if (!$this->aclService->isAllowed('insert', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to insert a job label'));
        }

        $labelDict = [];
        foreach ($this->languages as $lang) {
            $label = new JobLabelModel();
            $label->setLanguage($lang);
            $labelDict[$lang] = $label;
        }

        return $this->saveLabelData(null, $labelDict, $data);
    }

    /**
     * Checks if the data is valid, and if it is, saves the JobLabelModel.
     *
     * @param int|null $languageNeutralId Identifier of the JobLabelModel to save
     * @param array $labels The JobLabelModels to save
     * @param Parameters $data The data to validate, and apply to the label
     *
     * @return bool|int
     */
    public function saveLabelData(?int $languageNeutralId, array $labels, Parameters $data)
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit job labels'));
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

        return (null === $languageNeutralId) ? $id : $languageNeutralId;
    }

    /**
     * Sets the languageNeutralId for this JobLabelModel.
     *
     * @param int $id The id of the JobLabelModel
     * @param JobLabelModel $label The JobLabelModel
     * @param int|null $languageNeutralId The languageNeutralId of the JobLabelModel
     *
     * @return int
     */
    private function setLanguageNeutralLabelId(int $id, JobLabelModel $label, ?int $languageNeutralId): int
    {
        if (null === $languageNeutralId) {
            $label->setLanguageNeutralId($id);
            $this->labelMapper->persist($label);
            $this->saveLabel();

            if (-1 === $id) {
                $id = $label->getId();
            }

            $label->setLanguageNeutralId($id);

            return $id;
        }

        $label->setLanguageNeutralId($languageNeutralId);

        return $id;
    }

    /**
     * Checks if the data is valid, and if it is saves the package.
     *
     * @param mixed $package
     * @param Parameters $data
     * @param Parameters $files
     *
     * @return bool
     * @throws Exception
     */
    public function savePackageByData($package, Parameters $data, Parameters $files): bool
    {
        $packageForm = $this->getPackageForm();
        $packageForm->setData($data);

        if ($packageForm->isValid()) {
            $package->exchangeArray($data);

            if ('banner' == $package->getType()) {
                $file = $files['banner'];

                if (UPLOAD_ERR_NO_FILE !== $file['error']) {
                    if (UPLOAD_ERR_OK !== $file['error']) {
                        return false;
                    }

                    $oldPath = $package->getImage();
                    $newPath = $this->storageService->storeUploadedFile($file);
                    $package->setImage($newPath);

                    if ('' !== $oldPath && $oldPath !== $newPath) {
                        $this->storageService->removeFile($oldPath);
                    }
                }
            }

            $this->savePackage();

            return true;
        }

        return false;
    }

    /**
     * Checks if the data is valid, and if it is, saves the Company.
     *
     * @param CompanyModel $company
     * @param Parameters $data
     * @param Parameters $files
     *
     * @return bool
     *
     * @throws Exception
     */
    public function saveCompanyByData(CompanyModel $company, Parameters $data, Parameters $files): bool
    {
        $companyForm = $this->editCompanyForm;

        $dataArray = $data->toArray();
        $filesArray = $files->toArray();
        $mergedData = array_merge_recursive(
            $dataArray,
            $filesArray,
        );
        $companyForm->setData($mergedData);

        if ($companyForm->isValid()) {
            $company->exchangeArray($dataArray);

            foreach ($company->getTranslations() as $translation) {
                $file = $files[$translation->getLanguage() . '_logo'];

                if (UPLOAD_ERR_NO_FILE !== $file['error']) {
                    if (UPLOAD_ERR_OK !== $file['error']) {
                        return false;
                    }

                    $oldPath = $translation->getLogo();
                    $newPath = $this->storageService->storeUploadedFile($file);
                    $translation->setLogo($newPath);

                    if (null !== $oldPath && $oldPath != $newPath) {
                        $this->storageService->removeFile($oldPath);
                    }
                }
            }

            // Save the company data, removing any translations cannot be done in the same UnitOfWork.
            $this->persistCompany($company);

            // Remove translations if necessary.
            $enabledLanguages = $data['languages'];
            foreach ($company->getTranslations() as $translation) {
                if (!in_array($translation->getLanguage(), $enabledLanguages)) {
                    $company->removeTranslation($translation);
                    $this->companyMapper->remove($translation);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Saves all modified categories.
     */
    public function saveCategory()
    {
        $this->categoryMapper->flush();
    }

    /**
     * Saves all modified labels.
     */
    public function saveLabel()
    {
        $this->labelMapper->flush();
    }

    /**
     * Saves all modified jobs.
     */
    public function saveJob()
    {
        $this->jobMapper->flush();
    }

    /**
     * Saves all modified companies.
     */
    public function saveCompany(): void
    {
        $this->companyMapper->flush();
    }

    /**
     * @param CompanyModel $company
     *
     * @throws ORMException
     */
    public function persistCompany(CompanyModel $company): void
    {
        $this->companyMapper->persist($company);
    }

    /**
     * Saves all modified packages.
     */
    public function savePackage()
    {
        $this->packageMapper->flush();
    }

    /**
     * Checks if the data is valid, and if it is, inserts the company, and sets
     * all data.
     *
     * @param Parameters $data
     * @param Parameters $files
     *
     * @return CompanyModel|bool
     * @throws Exception
     */
    public function insertCompanyByData(Parameters $data, Parameters $files): CompanyModel|bool
    {
        $companyForm = $this->editCompanyForm;

        $dataArray = $data->toArray();
        $filesArray = $files->toArray();
        $mergedData = array_merge_recursive(
            $dataArray,
            $filesArray,
        );
        $companyForm->setData($mergedData);

        if ($companyForm->isValid()) {
            $company = $this->insertCompany($data['languages']);
            $company->exchangeArray($dataArray);

            foreach ($company->getTranslations() as $translation) {
                $file = $files[$translation->getLanguage() . '_logo'];

                if (UPLOAD_ERR_NO_FILE !== $file['error']) {
                    if (UPLOAD_ERR_OK !== $file['error']) {
                        return false;
                    }

                    $newPath = $this->storageService->storeUploadedFile($file);
                    $translation->setLogo($newPath);
                }
            }

            $this->saveCompany();

            return $company;
        }

        return false;
    }

    /**
     * Inserts the company and initializes translations for the given languages.
     *
     * @param array $languages
     *
     * @return CompanyModel
     * @throws ORMException
     */
    public function insertCompany(array $languages): CompanyModel
    {
        if (!$this->aclService->isAllowed('insert', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to insert a company'));
        }

        return $this->companyMapper->insert($languages);
    }

    /**
     * Checks if the data is valid, and if it is, inserts the package, and assigns it to the given company.
     *
     * @param string $companySlugName
     * @param Parameters $data
     * @param Parameters $files
     * @param string $type
     *
     * @return bool
     * @throws Exception
     */
    public function insertPackageForCompanySlugNameByData(
        string $companySlugName,
        Parameters $data,
        Parameters $files,
        string $type = 'job',
    ): bool {
        $packageForm = $this->getPackageForm($type);
        $packageForm->setData($data);

        if ($packageForm->isValid()) {
            $package = $this->insertPackageForCompanySlugName($companySlugName, $type);

            if ('banner' === $type) {
                $file = $files['banner'];

                if (UPLOAD_ERR_NO_FILE !== $file['error']) {
                    if (UPLOAD_ERR_OK !== $file['error']) {
                        return false;
                    }

                    $newPath = $this->storageService->storeUploadedFile($file);
                    $package->setImage($newPath);
                }
            }

            $package->exchangeArray($data->toArray());
            $this->savePackage();

            return true;
        }

        return false;
    }

    /**
     * Inserts a package and assigns it to the given company.
     *
     * @param mixed $companySlugName
     */
    public function insertPackageForCompanySlugName($companySlugName, $type = 'job')
    {
        if (!$this->aclService->isAllowed('insert', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to insert a package'));
        }

        $companies = $this->getEditableCompaniesBySlugName($companySlugName);
        $company = $companies[0];

        return $this->packageMapper->insertPackageIntoCompany($company, $type);
    }

    /**
     * Creates a new job and adds it to the specified package.
     *
     * @param int $packageId
     * @param Parameters $data
     * @param Parameters $files
     *
     * @return bool
     */
    public function createJob(int $packageId, Parameters $data, Parameters $files): bool
    {
        $package = $this->packageMapper->findPackage($packageId);
        $jobs = [];

        foreach ($this->languages as $lang) {
            $job = new JobModel();
            $job->setPackage($package);
            $job->setLanguage($lang);
            $jobs[$lang] = $job;
        }

        return $this->saveJobData(null, $jobs, $data, $files);
    }

    /**
     * Checks if the data is valid, and if it is, saves the Job.
     *
     * @param int|null $languageNeutralId Identifier of the Job to save
     * @param array $jobs The Job to save
     * @param Parameters $data The (new) data to save
     * @param Parameters $files The (new) files to save
     *
     * @return bool
     */
    public function saveJobData(?int $languageNeutralId, array $jobs, Parameters $data, Parameters $files): bool
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit jobs'));
        }

        $jobForm = $this->getJobForm();
        $dataArray = $data->toArray();
        $filesArray = $files->toArray();
        $mergedData = array_merge_recursive(
            $dataArray,
            $filesArray,
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

            if (null !== $file && UPLOAD_ERR_NO_FILE !== $file['error']) {
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
     * @param JobModel $job
     * @param array $labels
     */
    private function setLabelsForJob(JobModel $job, array $labels): void
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
     * @param JobModel $job
     * @param array $labels
     */
    private function addLabelsToJob(JobModel $job, array $labels): void
    {
        $mapperLabel = $this->labelMapper;
        $mapperLabelAssignment = $this->labelAssignmentMapper;
        $mapperJob = $this->jobMapper;

        foreach ($labels as $label) {
            $jobLabelAssignment = new JobLabelAssignmentModel();
            $labelModel = $mapperLabel->findLabelById($label);
            $jobLabelAssignment->setLabel($labelModel);
            $job->addLabel($jobLabelAssignment);
            $mapperLabelAssignment->persist($jobLabelAssignment);
            $mapperJob->flush();
        }
    }

    /**
     * @param JobModel $job
     * @param array $labels
     */
    private function removeLabelsFromJob(JobModel $job, array $labels): void
    {
        $mapper = $this->labelAssignmentMapper;

        foreach ($labels as $label) {
            $toRemove = $mapper->findAssignmentByJobIdAndLabelId($job->getId(), $label);
            $mapper->remove($toRemove);
        }
    }

    /**
     * @param int $id
     * @param JobModel $job
     * @param int|null $languageNeutralId
     *
     * @return int
     */
    private function setLanguageNeutralJobId(int $id, JobModel $job, ?int $languageNeutralId): int
    {
        if (null === $languageNeutralId) {
            $job->setLanguageNeutralId($id);
            $this->jobMapper->persist($job);
            $this->saveJob();

            if (-1 === $id) {
                $id = $job->getId();
            }

            $job->setLanguageNeutralId($id);

            return $id;
        }

        $job->setLanguageNeutralId($languageNeutralId);

        return $id;
    }

    /**
     * Inserts a job, and binds it to the given package.
     *
     * @param mixed $packageId
     */
    public function insertJobIntoPackageId($packageId, $lang, $languageNeutralId)
    {
        if (!$this->aclService->isAllowed('insert', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to insert a job'));
        }
        $package = $this->getEditablePackage($packageId);

        return $this->jobMapper->insertIntoPackage($package, $lang, $languageNeutralId);
    }

    /**
     * Deletes the given package.
     *
     * @param mixed $packageId
     */
    public function deletePackage($packageId)
    {
        if (!$this->aclService->isAllowed('delete', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete packages'));
        }
        $this->packageMapper->deletePackage($packageId);
        $this->bannerPackageMapper->deletePackage($packageId);
    }

    /**
     * Deletes the given job.
     *
     * @param int $jobId
     */
    public function deleteJob($jobId)
    {
        if (!$this->aclService->isAllowed('delete', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete jobs'));
        }
        $this->jobMapper->deleteByLanguageNeutralId($jobId);
    }

    /**
     * Deletes the company identified with $slug.
     *
     * @param mixed $slug
     */
    public function deleteCompaniesBySlug($slug)
    {
        if (!$this->aclService->isAllowed('delete', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete companies'));
        }
        $company = $this->getCompanyBySlugName($slug);
        $this->companyMapper->remove($company);
    }

    /**
     * Return the company identified by $slugName.
     *
     * @param string $slugName
     */
    public function getCompanyBySlugName($slugName)
    {
        return $this->companyMapper->findCompanyBySlugName($slugName);
    }

    /**
     * Returns a persistent category.
     *
     * @param int $categoryId
     */
    public function getAllCategoriesById($categoryId)
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit packages'));
        }

        return $this->categoryMapper->findAllCategoriesById($categoryId);
    }

    /**
     * Returns a persistent label.
     *
     * @param int $labelId
     */
    public function getAllLabelsById($labelId)
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit packages'));
        }

        return $this->labelMapper->findAllLabelsById($labelId);
    }

    /**
     * Returns a persistent package.
     *
     * @param mixed $packageId
     */
    public function getEditablePackage($packageId)
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit packages'));
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
     * Returns all companies with a given $slugName and makes them persistent.
     *
     * @param mixed $slugName
     */
    public function getEditableCompaniesBySlugName($slugName)
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit companies'));
        }

        return $this->companyMapper->findEditableCompaniesBySlugName($slugName, true);
    }

    /**
     * Returns all jobs with a given slugname, owned by a company with
     * $companySlugName.
     *
     * @param int $languageNeutralId
     * @return int|mixed|string
     */
    public function getEditableJobsByLanguageNeutralId($languageNeutralId)
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit jobs'));
        }

        return $this->jobMapper->findJob(['languageNeutralId' => $languageNeutralId]);
    }

    /**
     * Get the Category Edit form.
     *
     * @return EditCategoryForm For for editing JobCategories
     */
    public function getCategoryForm(): EditCategoryForm
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit categories'));
        }

        return $this->editCategoryForm;
    }

    /**
     * Get the Label Edit form.
     *
     * @return EditLabelForm Form for editing JobLabelModels
     */
    public function getLabelForm(): EditLabelForm
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit labels'));
        }

        return $this->editLabelForm;
    }

    /**
     * Returns a the form for entering packages.
     *
     * @return EditPackageForm Form
     */
    public function getPackageForm($type = 'job'): EditPackageForm
    {
        if ('banner' === $type) {
            return $this->editBannerPackageForm;
        }

        if ('featured' === $type) {
            return $this->editFeaturedPackageForm;
        }

        return $this->editPackageForm;
    }

    /**
     * Returns the form for entering jobs.
     *
     * @return EditJobForm Job edit form
     */
    public function getJobForm(): EditJobForm
    {
        if (!$this->aclService->isAllowed('edit', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit jobs'));
        }

        return $this->editJobForm;
    }

    /**
     * Get the default resource Id.
     *
     * @return string
     */
    protected function getDefaultResourceId(): string
    {
        return 'company';
    }

    public static function getLanguageDescription($lang): string
    {
        if ('en' === $lang) {
            return 'English';
        }

        return 'Dutch';
    }
}
