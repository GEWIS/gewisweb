<?php

namespace Company\Service;

use Application\Model\Enums\ApprovableStatus;
use Application\Service\FileStorage;
use Company\Form\{
    Company as CompanyForm,
    Job as EditJobForm,
    JobCategory as EditCategoryForm,
    JobLabel as EditLabelForm,
    Package as EditPackageForm,
};
use Company\Mapper\{
    BannerPackage as BannerPackageMapper,
    Category as CategoryMapper,
    Company as CompanyMapper,
    FeaturedPackage as FeaturedPackageMapper,
    Job as JobMapper,
    Label as LabelMapper,
    Package as PackageMapper,
};
use Company\Model\{
    Company as CompanyModel,
    CompanyBannerPackage as CompanyBannerPackageModel,
    CompanyFeaturedPackage as CompanyFeaturedPackageModel,
    CompanyJobPackage as CompanyJobPackageModel,
    CompanyLocalisedText,
    CompanyPackage as CompanyPackageModel,
    Job as JobModel,
    JobCategory as JobCategoryModel,
    JobLabel as JobLabelModel,
    Proposals\CompanyUpdate as CompanyUpdateProposal,
    Proposals\JobUpdate as JobUpdateProposal};
use DateTime;
use Doctrine\ORM\{
    Exception\ORMException,
    NonUniqueResultException,
};
use Exception;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;
use User\Service\User as UserService;

/**
 * Company service.
 */
class Company
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly FileStorage $storageService,
        private readonly CompanyMapper $companyMapper,
        private readonly PackageMapper $packageMapper,
        private readonly BannerPackageMapper $bannerPackageMapper,
        private readonly FeaturedPackageMapper $featuredPackageMapper,
        private readonly JobMapper $jobMapper,
        private readonly CategoryMapper $categoryMapper,
        private readonly LabelMapper $labelMapper,
        private readonly CompanyForm $companyForm,
        private readonly EditPackageForm $editPackageForm,
        private readonly EditPackageForm $editBannerPackageForm,
        private readonly EditPackageForm $editFeaturedPackageForm,
        private readonly EditJobForm $editJobForm,
        private readonly EditCategoryForm $editCategoryForm,
        private readonly EditLabelForm $editLabelForm,
        private readonly UserService $userService,
    ) {
    }

    /**
     * Returns a random banner for display on the frontpage.
     *
     * @return CompanyBannerPackageModel|null
     */
    public function getCurrentBanner(): ?CompanyBannerPackageModel
    {
        if (!$this->aclService->isAllowed('viewBanner', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the banner'));
        }

        return $this->bannerPackageMapper->getBannerPackage();
    }

    /**
     * @return CompanyFeaturedPackageModel|null
     */
    public function getFeaturedPackage(): ?CompanyFeaturedPackageModel
    {
        if (!$this->aclService->isAllowed('viewFeatured', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the featured company')
            );
        }

        return $this->featuredPackageMapper->getFeaturedPackage();
    }

    /**
     * @param DateTime $date
     *
     * @return array
     */
    private function getFuturePackageStartsBeforeDate(DateTime $date): array
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

    /**
     * @param DateTime $date
     *
     * @return array
     */
    private function getFuturePackageExpiresBeforeDate(DateTime $date): array
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
    public function getPackageChangeEvents(DateTime $date): array
    {
        if (!$this->aclService->isAllowed('listAll', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list the companies'));
        }

        $startPackages = $this->getFuturePackageStartsBeforeDate($date);
        $expirePackages = $this->getFuturePackageExpiresBeforeDate($date);

        return [
            $startPackages,
            $expirePackages,
        ];
    }

    /**
     * Returns a list of all companies (excluding hidden companies).
     *
     * @return array
     */
    public function getCompanyList(): array
    {
        if (!$this->aclService->isAllowed('list', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list the companies'));
        }

        return $this->companyMapper->findAllPublic();
    }

    /**
     * Returns a list of all companies (including hidden companies).
     *
     * @return array
     */
    public function getHiddenCompanyList(): array
    {
        if (!$this->aclService->isAllowed('listAll', 'company')) {
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
    public function getCompanyById(int $id): ?CompanyModel
    {
        if (!$this->aclService->isAllowed('listAll', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list the companies'));
        }

        return $this->companyMapper->find($id);
    }

    /**
     * @param string $slug
     *
     * @return JobCategoryModel|null
     * @throws NonUniqueResultException
     */
    public function getJobCategoryBySlug(string $slug): ?JobCategoryModel
    {
        return $this->categoryMapper->findCategoryBySlug($slug);
    }

    /**
     * Creates a new JobCategoryModel.
     *
     * @param array $data Category data from the JobCategory form
     *
     * @return JobCategoryModel
     */
    public function createJobCategory(array $data): JobCategoryModel
    {
        $jobCategory = new JobCategoryModel();

        $jobCategory->setName(new CompanyLocalisedText($data['nameEn'], $data['name']));
        $jobCategory->setPluralName(new CompanyLocalisedText($data['pluralNameEn'], $data['pluralName']));
        $jobCategory->setSlug(new CompanyLocalisedText($data['slugEn'], $data['slug']));
        $jobCategory->setHidden($data['hidden']);

        $this->persistJobCategory($jobCategory);

        return $jobCategory;
    }

    /**
     * @param JobCategoryModel $jobCategory The JobCategoryModel to update
     * @param array $data The (new) data to save
     */
    public function updateJobCategory(
        JobCategoryModel $jobCategory,
        array $data,
    ): void {
        $jobCategory->getName()->updateValues($data['nameEn'], $data['name']);
        $jobCategory->getPluralName()->updateValues($data['pluralNameEn'], $data['pluralName']);
        $jobCategory->getSlug()->updateValues($data['slugEn'], $data['slug']);
        $jobCategory->setHidden($data['hidden']);

        $this->persistJobCategory($jobCategory);
    }

    /**
     * Creates a new JobLabelModel.
     *
     * @param array $data Label data from the JobLabelForm
     *
     * @return JobLabelModel
     */
    public function createJobLabel(array $data): JobLabelModel
    {
        $jobLabel = new JobLabelModel();

        $jobLabel->setName(new CompanyLocalisedText($data['nameEn'], $data['name']));
        $jobLabel->setAbbreviation(new CompanyLocalisedText($data['abbreviationEn'], $data['abbreviation']));

        $this->persistJobLabel($jobLabel);

        return $jobLabel;
    }

    /**
     * Updates the JobLabelModel.
     *
     * @param JobLabelModel $jobLabel
     * @param array $data The data to validate, and apply to the label
     */
    public function updateJobLabel(
        JobLabelModel $jobLabel,
        array $data,
    ): void {
        $jobLabel->getName()->updateValues($data['nameEn'], $data['name']);
        $jobLabel->getAbbreviation()->updateValues($data['abbreviationEn'], $data['abbreviation']);

        $this->persistJobLabel($jobLabel);
    }

    /**
     * Inserts the company and initializes translations for the given languages.
     *
     * @param array $data
     *
     * @return CompanyModel|bool
     *
     * @throws ORMException
     */
    public function createCompany(array $data): CompanyModel|bool
    {
        $company = new CompanyModel();

        // Set attributes that are not L10n-able.
        $company->setName($data['name']);
        $company->setSlugName($data['slugName']);
        $company->setPublished($data['published']);

        $company->setRepresentativeName($data['representativeName']);
        $company->setRepresentativeEmail($data['representativeEmail']);

        $company->setContactName($data['contactName']);
        $company->setContactEmail($data['contactEmail']);
        $company->setContactPhone($data['contactPhone']);
        $company->setContactAddress($data['contactAddress']);

        // Set all attributes that are L10n-able.
        $company->setSlogan(new CompanyLocalisedText($data['sloganEn'], $data['slogan']));
        $company->setWebsite(new CompanyLocalisedText($data['websiteEn'], $data['website']));
        $company->setDescription(new CompanyLocalisedText($data['descriptionEn'], $data['description']));

        // If the user can approve (changes to) companies, directly approve the company.
        if ($this->aclService->isAllowed('approve', 'company')) {
            $company->setApproved(ApprovableStatus::Approved);
            $company->setApprovedAt(new DateTime());
            $company->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());
        } else {
            $company->setApproved(ApprovableStatus::Unapproved);
        }

        // Upload the logo of the company.
        if (!$this->uploadFile($company, $data['logo'])) {
            return false;
        }

        $this->persistCompany($company);

        $this->userService->registerCompanyUser($company);

        return $company;
    }

    /**
     * Updates a company with the provided data.
     *
     * @param CompanyModel $company
     * @param array $data
     *
     * @return bool
     *
     * @throws Exception
     */
    public function updateCompany(
        CompanyModel $company,
        array $data,
    ): bool {
        // If the user can approve changes to companies, directly apply the changes to the company.
        if ($this->aclService->isAllowed('approve', 'company')) {
            $company->exchangeArray($data);

            $company->setApproved(ApprovableStatus::Approved);
            $company->setApprovedAt(new DateTime());
            $company->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());

            // Upload the logo of the company.
            if (!$this->uploadFile($company, $data['logo'])) {
                return false;
            }
        } else {
            // If the user does not have the privileges to approve changes to a company, create an update proposal.
            $updateProposal = $this->createCompany($data);

            if (!$updateProposal instanceof CompanyModel) {
                return false;
            }

            $companyUpdateProposal = new CompanyUpdateProposal();
            $companyUpdateProposal->setCurrent($company);
            $companyUpdateProposal->setProposal($updateProposal);

            $this->companyMapper->persist($updateProposal);

            // TODO: Send e-mail to CEB/C4 about proposed changes.
        }

        $this->persistCompany($company);

        return true;
    }

    /**
     * A function which uploads an image. Is used for uploading company logos, banner package banners, and attachments
     * of jobs. It assumes that if the file is null (i.e. no image has been submitted) it should not touch the old
     * file.
     *
     * @param CompanyModel|CompanyPackageModel|JobModel $entity
     * @param array|null $file
     * @param string $languageSuffix
     *
     * @return bool
     *
     * @throws Exception
     */
    private function uploadFile(
        CompanyModel|CompanyPackageModel|JobModel $entity,
        ?array $file,
        string $languageSuffix = '',
    ): bool {
        if (null === $file) {
            return true;
        }

        // Check if there is an actual file and no errors occurred during the upload.
        if (UPLOAD_ERR_NO_FILE !== $file['error']) {
            if (UPLOAD_ERR_OK !== $file['error']) {
                return false;
            }

            // Save the file to persistent storage.
            $path = $this->storageService->storeUploadedFile($file);

            if ($entity instanceof CompanyModel) {
                $oldPath = $entity->getLogo();
                $entity->setLogo($path);
            }

            if ($entity instanceof CompanyBannerPackageModel) {
                $oldPath = $entity->getImage();
                $entity->setImage($path);
            }

            if ($entity instanceof JobModel) {
                if ('' === $languageSuffix) {
                    $oldPath = $entity->getAttachment()->getValueNL();
                    $entity->getAttachment()->updateValueNL($path);
                } elseif ('En' === $languageSuffix) {
                    $oldPath = $entity->getAttachment()->getValueEN();
                    $entity->getAttachment()->updateValueEN($path);
                }
            }

            // Remove the old logo from storage.
            if (isset($oldPath) && $oldPath !== $path) {
                $this->storageService->removeFile($oldPath);
            }
        }

        return true;
    }

    /**
     * Saves the modified JobCategoryModel.
     *
     * @param JobCategoryModel $jobCategory
     */
    public function persistJobCategory(JobCategoryModel $jobCategory): void
    {
        $this->categoryMapper->persist($jobCategory);
    }

    /**
     * Saves the modified JobLabelModel.
     *
     * @param JobLabelModel $jobLabel
     */
    public function persistJobLabel(JobLabelModel $jobLabel): void
    {
        $this->labelMapper->persist($jobLabel);
    }

    /**
     * Saves all modified jobs.
     *
     * @param JobModel $job
     *
     * @throws ORMException
     */
    public function persistJob(JobModel $job): void
    {
        $this->jobMapper->persist($job);
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
     *
     * @param CompanyPackageModel $package
     *
     * @throws ORMException
     */
    public function persistPackage(CompanyPackageModel $package): void
    {
        $this->packageMapper->persist($package);
    }

    /**
     * Creates a new package, and assigns it to the given company.
     *
     * @param CompanyModel $company
     * @param array $data
     * @param string $type
     *
     * @return bool
     *
     * @throws Exception
     */
    public function createPackage(
        CompanyModel $company,
        array $data,
        string $type = 'job',
    ): bool {
        $package = $this->packageMapper->createPackage($type);
        $package->setCompany($company);

        if (CompanyBannerPackageModel::class === get_class($package)) {
            if (!$this->uploadFile($package, $data['banner'])) {
                return false;
            }
        }

        $package->exchangeArray($data);
        $this->persistPackage($package);

        return true;
    }

    /**
     * Updates the package.
     *
     * @param CompanyPackageModel $package
     * @param array $data
     *
     * @return bool
     *
     * @throws Exception
     */
    public function updatePackage(
        CompanyPackageModel $package,
        array $data,
    ): bool {
        if (CompanyBannerPackageModel::class === get_class($package)) {
            if (!$this->uploadFile($package, $data['banner'])) {
                return false;
            }
        }

        $package->exchangeArray($data);
        $this->persistPackage($package);

        return true;
    }

    /**
     * Creates a new job and adds it to the specified package.
     *
     * @param CompanyJobPackageModel $package
     * @param array $data
     *
     * @return JobModel|bool
     *
     * @throws ORMException
     */
    public function createJob(
        CompanyJobPackageModel $package,
        array $data,
    ): JobModel|bool {
        $job = new JobModel();

        $category = $this->categoryMapper->find($data['category']);
        if (null === $category) {
            return false;
        }

        $job->setSlugName($data['slugName']);
        $job->setCategory($category);
        $job->setPublished($data['published']);
        $job->setContactName($data['contactName']);
        $job->setContactEmail($data['contactEmail']);
        $job->setContactPhone($data['contactPhone']);

        $job->setName(new CompanyLocalisedText($data['nameEn'], $data['name']));
        $job->setLocation(new CompanyLocalisedText($data['locationEn'], $data['location']));
        $job->setWebsite(new CompanyLocalisedText($data['websiteEn'], $data['website']));
        $job->setDescription(new CompanyLocalisedText($data['descriptionEn'], $data['description']));
        $job->setAttachment(new CompanyLocalisedText(null, null));

        if (isset($data['labels'])) {
            foreach ($data['labels'] as $label) {
                $label = $this->getJobLabelById($label);

                if (null !== $label) {
                    $job->addLabel($label);
                }
            }
        }

        $job->setPackage($package);
        $package->addJob($job);

        // If the user can approve (changed to) jobs, directly approve the job.
        if ($this->aclService->isAllowed('approve', 'job')) {
            $job->setApproved(ApprovableStatus::Approved);
            $job->setApprovedAt(new DateTime());
            $job->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());
        } else {
            $job->setApproved(ApprovableStatus::Unapproved);
        }

        // Upload the attachments.
        if (!$this->uploadFile($job, $data['attachment'])) {
            return false;
        }

        if (!$this->uploadFile($job, $data['attachmentEn'], 'En')) {
            return false;
        }

        $this->persistJob($job);

        return $job;
    }

    /**
     * @param JobModel $job
     * @param array $data
     *
     * @return bool
     *
     * @throws ORMException
     */
    public function updateJob(
        JobModel $job,
        array $data,
    ): bool {
        // If the user can approve changes to jobs, directly apply the changes to the job.
        if ($this->aclService->isAllowed('approve', 'job')) {
            $category = $this->categoryMapper->find($data['category']);

            if (null === $category) {
                return false;
            }

            $job->setSlugName($data['slugName']);
            $job->setCategory($category);
            $job->setPublished($data['published']);
            $job->setContactName($data['contactName']);
            $job->setContactEmail($data['contactEmail']);
            $job->setContactPhone($data['contactPhone']);

            $job->getName()->updateValues($data['nameEn'], $data['name']);
            $job->getLocation()->updateValues($data['locationEn'], $data['location']);
            $job->getWebsite()->updateValues($data['websiteEn'], $data['website']);
            $job->getDescription()->updateValues($data['descriptionEn'], $data['description']);

            if (isset($data['labels'])) {
                $newLabels = $data['labels'];
                $currentLabels = $job->getLabels()->map(function ($label) {
                    return $label->getId();
                })->toArray();

                $intersection = array_intersect($newLabels, $currentLabels);
                $toRemove = array_diff($currentLabels, $newLabels);
                $toAdd = array_diff($newLabels, $intersection);

                foreach ($toRemove as $label) {
                    $label = $this->getJobLabelById($label);
                    $job->removeLabel($label);
                }

                foreach ($toAdd as $label) {
                    $label = $this->getJobLabelById($label);

                    if (null !== $label) {
                        $job->addLabel($label);
                    }
                }
            }

            // Upload the attachments.
            if (!$this->uploadFile($job, $data['attachment'])) {
                return false;
            }

            if (!$this->uploadFile($job, $data['attachmentEn'], 'En')) {
                return false;
            }
        } else {
            // If the user does not have the privileges to approve changes to a job, create an update proposal.
            $updateProposal = $this->createJob($job->getPackage(), $data);

            if (!$updateProposal instanceof JobModel) {
                return false;
            }

            $companyUpdateProposal = new JobUpdateProposal();
            $companyUpdateProposal->setCurrent($job);
            $companyUpdateProposal->setProposal($updateProposal);

            $this->jobMapper->persist($updateProposal);

            // TODO: Send e-mail to CEB/C4 about proposed changes.
        }

        $this->persistJob($job);

        return true;
    }

    /**
     * Deletes the given package.
     *
     * @param CompanyPackageModel $package
     *
     * @throws ORMException
     */
    public function deletePackage(CompanyPackageModel $package): void
    {
        if (!$this->aclService->isAllowed('delete', 'package')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete packages'));
        }

        $this->packageMapper->remove($package);
    }

    /**
     * Deletes the given job.
     *
     * @param JobModel $job
     *
     * @throws ORMException
     */
    public function deleteJob(JobModel $job): void
    {
        if (!$this->aclService->isAllowed('delete', 'job')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete jobs'));
        }

        $this->jobMapper->remove($job);
    }

    /**
     * Deletes the company identified with $slug.
     *
     * @param string $slug
     *
     * @throws ORMException
     */
    public function deleteCompanyBySlug(string $slug): void
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
     *
     * @return CompanyModel|null
     */
    public function getCompanyBySlugName(string $slugName): ?CompanyModel
    {
        return $this->companyMapper->findCompanyBySlugName($slugName);
    }

    /**
     * Returns a persistent category.
     *
     * @param int $jobCategoryId
     *
     * @return JobCategoryModel|null
     */
    public function getJobCategoryById(int $jobCategoryId): ?JobCategoryModel
    {
        if (!$this->aclService->isAllowed('listAll', 'jobCategory')) {
            if ($this->aclService->isAllowed('list', 'jobCategory')) {
                return $this->categoryMapper->findVisibleCategoryById($jobCategoryId);
            }

            throw new NotAllowedException($this->translator->translate('You are not allowed to edit job categories'));
        }

        return $this->categoryMapper->find($jobCategoryId);
    }

    /**
     * Returns a persistent label.
     *
     * @param int $jobLabelId
     *
     * @return JobLabelModel|null
     */
    public function getJobLabelById(int $jobLabelId): ?JobLabelModel
    {
        if (!$this->aclService->isAllowed('listAll', 'jobLabel')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit job labels'));
        }

        return $this->labelMapper->find($jobLabelId);
    }

    /**
     * Returns a persistent package.
     *
     * @param int $packageId
     *
     * @return CompanyPackageModel|null
     */
    public function getPackageById(int $packageId): ?CompanyPackageModel
    {
        if (!$this->aclService->isAllowed('edit', 'package')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit packages'));
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
     * Returns all jobs with a given slugname, owned by a company with
     * $companySlugName.
     *
     * @param int $jobId
     *
     * @return JobModel|null
     */
    public function getJobById(int $jobId): ?JobModel
    {
        if (!$this->aclService->isAllowed('edit', 'job')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit jobs'));
        }

        return $this->jobMapper->find($jobId);
    }

    /**
     * Get the Category Edit form.
     *
     * @return EditCategoryForm For for editing JobCategories
     */
    public function getCategoryForm(): EditCategoryForm
    {
        if (
            !$this->aclService->isAllowed('create', 'jobCategory')
            && !$this->aclService->isAllowed('edit', 'jobCategory')
        ) {
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
        if (
            !$this->aclService->isAllowed('create', 'jobLabel')
            && !$this->aclService->isAllowed('edit', 'jobLabel')
        ) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit labels'));
        }

        return $this->editLabelForm;
    }

    /**
     * Returns a the form for entering packages.
     *
     * @param string $type
     *
     * @return EditPackageForm Form
     */
    public function getPackageForm(string $type = 'job'): EditPackageForm
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
        if (
            !$this->aclService->isAllowed('create', 'job')
            && !$this->aclService->isAllowed('edit', 'job')
            && !$this->aclService->isAllowed('createOwn', 'job')
            && !$this->aclService->isAllowed('editOwn', 'job')
        ) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit jobs'));
        }

        return $this->editJobForm;
    }

    /**
     * @return CompanyForm
     */
    public function getCompanyForm(): CompanyForm
    {
        if (
            !$this->aclService->isAllowed('create', 'company')
            && !$this->aclService->isAllowed('edit', 'company')
            && !$this->aclService->isAllowed('editOwn', 'company')
        ) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create a company'));
        }

        return $this->companyForm;
    }
}
