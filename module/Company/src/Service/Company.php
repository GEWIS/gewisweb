<?php

declare(strict_types=1);

namespace Company\Service;

use Application\Model\ApprovableText as ApprovableTextModel;
use Application\Model\Enums\ApprovableStatus;
use Application\Service\FileStorage;
use Company\Form\Company as CompanyForm;
use Company\Form\Job as EditJobForm;
use Company\Form\JobCategory as EditCategoryForm;
use Company\Form\JobLabel as EditLabelForm;
use Company\Form\Package as EditPackageForm;
use Company\Mapper\BannerPackage as BannerPackageMapper;
use Company\Mapper\Category as CategoryMapper;
use Company\Mapper\Company as CompanyMapper;
use Company\Mapper\FeaturedPackage as FeaturedPackageMapper;
use Company\Mapper\Job as JobMapper;
use Company\Mapper\JobUpdate as JobUpdateMapper;
use Company\Mapper\Label as LabelMapper;
use Company\Mapper\Package as PackageMapper;
use Company\Model\Company as CompanyModel;
use Company\Model\CompanyBannerPackage as CompanyBannerPackageModel;
use Company\Model\CompanyFeaturedPackage as CompanyFeaturedPackageModel;
use Company\Model\CompanyJobPackage as CompanyJobPackageModel;
use Company\Model\CompanyLocalisedText;
use Company\Model\CompanyPackage as CompanyPackageModel;
use Company\Model\Enums\CompanyPackageTypes;
use Company\Model\Job as JobModel;
use Company\Model\JobCategory as JobCategoryModel;
use Company\Model\JobLabel as JobLabelModel;
use Company\Model\Proposals\CompanyUpdate as CompanyUpdateProposal;
use Company\Model\Proposals\JobUpdate as JobUpdateProposalModel;
use DateTime;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;
use User\Service\User as UserService;

use function array_diff;
use function array_intersect;
use function array_map;
use function array_merge;
use function boolval;
use function intval;
use function trim;
use function usort;

use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * Company service.
 */
class Company
{
    /**
     * @psalm-param PackageMapper<CompanyJobPackageModel> $packageMapper
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly FileStorage $storageService,
        private readonly CompanyMapper $companyMapper,
        private readonly PackageMapper $packageMapper,
        private readonly BannerPackageMapper $bannerPackageMapper,
        private readonly FeaturedPackageMapper $featuredPackageMapper,
        private readonly JobMapper $jobMapper,
        private readonly JobUpdateMapper $jobUpdateMapper,
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
     */
    public function getCurrentBanner(): ?CompanyBannerPackageModel
    {
        if (!$this->aclService->isAllowed('viewBanner', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view the banner'));
        }

        return $this->bannerPackageMapper->getBannerPackage();
    }

    public function getFeaturedPackage(): ?CompanyFeaturedPackageModel
    {
        if (!$this->aclService->isAllowed('viewFeatured', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the featured company'),
            );
        }

        return $this->featuredPackageMapper->getFeaturedPackage();
    }

    /**
     * @return CompanyPackageModel[]
     */
    private function getFuturePackageStartsBeforeDate(DateTime $date): array
    {
        $startPackages = array_merge(
            $this->packageMapper->findFuturePackageStartsBeforeDate($date),
            $this->bannerPackageMapper->findFuturePackageStartsBeforeDate($date),
            $this->featuredPackageMapper->findFuturePackageStartsBeforeDate($date),
        );

        usort($startPackages, static function ($a, $b) {
            $aStart = $a->getStartingDate();
            $bStart = $b->getStartingDate();
            if ($aStart === $bStart) {
                return 0;
            }

            return $aStart < $bStart ? -1 : 1;
        });

        return $startPackages;
    }

    /**
     * @return CompanyPackageModel[]
     */
    private function getFuturePackageExpiresBeforeDate(DateTime $date): array
    {
        $expirePackages = array_merge(
            $this->packageMapper->findFuturePackageExpirationsBeforeDate($date),
            $this->bannerPackageMapper->findFuturePackageExpirationsBeforeDate($date),
            $this->featuredPackageMapper->findFuturePackageExpirationsBeforeDate($date),
        );

        usort($expirePackages, static function ($a, $b) {
            $aEnd = $a->getExpirationDate();
            $bEnd = $b->getExpirationDate();
            if ($aEnd === $bEnd) {
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
     * @psalm-return array{
     *     0: CompanyPackageModel[],
     *     1: CompanyPackageModel[],
     * } Two sorted arrays, containing the packages that respectively start and expire between now and $date
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
     * @return CompanyModel[]
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
     * @return CompanyModel[]
     */
    public function getHiddenCompanyList(): array
    {
        if (!$this->aclService->isAllowed('listAll', 'company')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to access the admin interface'),
            );
        }

        return $this->companyMapper->findAll();
    }

    /**
     * Get public company by id.
     */
    public function getCompanyById(int $id): ?CompanyModel
    {
        if (!$this->aclService->isAllowed('listAll', 'company')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list the companies'));
        }

        return $this->companyMapper->find($id);
    }

    /**
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function createJobCategory(array $data): JobCategoryModel
    {
        $jobCategory = new JobCategoryModel();

        $jobCategory->setName(new CompanyLocalisedText($data['nameEn'], $data['name']));
        $jobCategory->setPluralName(new CompanyLocalisedText($data['pluralNameEn'], $data['pluralName']));
        $jobCategory->setSlug(new CompanyLocalisedText($data['slugEn'], $data['slug']));
        $jobCategory->setHidden(boolval($data['hidden']));

        $this->persistJobCategory($jobCategory);

        return $jobCategory;
    }

    /**
     * @param JobCategoryModel $jobCategory The JobCategoryModel to update
     * @param array            $data        The (new) data to save
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function updateJobCategory(
        JobCategoryModel $jobCategory,
        array $data,
    ): void {
        $jobCategory->getName()->updateValues($data['nameEn'], $data['name']);
        $jobCategory->getPluralName()->updateValues($data['pluralNameEn'], $data['pluralName']);
        $jobCategory->getSlug()->updateValues($data['slugEn'], $data['slug']);
        $jobCategory->setHidden(boolval($data['hidden']));

        $this->persistJobCategory($jobCategory);
    }

    /**
     * Creates a new JobLabelModel.
     *
     * @param array $data Label data from the JobLabelForm
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
     * @param array $data The data to validate, and apply to the label
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
     * @throws ORMException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function createCompany(array $data): CompanyModel|bool
    {
        $company = new CompanyModel();

        // Set attributes that are not L10n-able.
        $company->setName($data['name']);
        $company->setSlugName($data['slugName']);
        $company->setPublished(boolval($data['published']));

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
     * @param array $data
     *
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
            $companyUpdateProposal->setOriginal($company);
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
     * @param array|null $file
     *
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
     */
    public function persistJobCategory(JobCategoryModel $jobCategory): void
    {
        $this->categoryMapper->persist($jobCategory);
    }

    /**
     * Saves the modified JobLabelModel.
     */
    public function persistJobLabel(JobLabelModel $jobLabel): void
    {
        $this->labelMapper->persist($jobLabel);
    }

    /**
     * Saves all modified jobs.
     *
     * @throws ORMException
     */
    public function persistJob(JobModel $job): void
    {
        $this->jobMapper->persist($job);
    }

    /**
     * @throws ORMException
     */
    public function persistCompany(CompanyModel $company): void
    {
        $this->companyMapper->persist($company);
    }

    /**
     * Saves all modified packages.
     *
     * @throws ORMException
     */
    public function persistPackage(CompanyPackageModel $package): void
    {
        if ($package instanceof CompanyBannerPackageModel) {
            $this->bannerPackageMapper->persist($package);
        } elseif ($package instanceof CompanyFeaturedPackageModel) {
            $this->featuredPackageMapper->persist($package);
        } elseif ($package instanceof CompanyJobPackageModel) {
            $this->packageMapper->persist($package);
        }
    }

    /**
     * Creates a new package, and assigns it to the given company.
     *
     * @param array $data
     *
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function createPackage(
        CompanyModel $company,
        array $data,
        CompanyPackageTypes $type = CompanyPackageTypes::Job,
    ): bool {
        $package = $this->packageMapper->createPackage($type);
        $package->setCompany($company);

        if (CompanyPackageTypes::Banner === $type) {
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
     * @param array $data
     *
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function updatePackage(
        CompanyPackageModel $package,
        array $data,
    ): bool {
        if (CompanyPackageTypes::Banner === $package->getType()) {
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
     * @param array $data
     *
     * @throws ORMException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function createJob(
        CompanyJobPackageModel $package,
        array $data,
    ): JobModel|bool {
        $category = $this->categoryMapper->find($data['category']);
        if (null === $category) {
            return false;
        }

        $job = new JobModel();
        $job->setSlugName($data['slugName']);
        $job->setCategory($category);
        $job->setPublished(boolval($data['published']));
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
                $label = $this->getJobLabelById(intval($label));

                if (null === $label) {
                    continue;
                }

                $job->addLabel($label);
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
     * @param array $data
     *
     * @throws ORMException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function updateJob(
        JobModel $job,
        array $data,
        bool $applyProposal = false,
    ): bool {
        // If the user can approve changes to jobs, directly apply the changes to the job.
        if (
            $this->aclService->isAllowed('approve', 'job')
            || $applyProposal
        ) {
            $category = $this->categoryMapper->find($data['category']);

            if (null === $category) {
                return false;
            }

            $job->setSlugName($data['slugName']);
            $job->setCategory($category);
            $job->setPublished(boolval($data['published']));
            $job->setContactName($data['contactName']);
            $job->setContactEmail($data['contactEmail']);
            $job->setContactPhone($data['contactPhone']);

            $job->getName()->updateValues($data['nameEn'], $data['name']);
            $job->getLocation()->updateValues($data['locationEn'], $data['location']);
            $job->getWebsite()->updateValues($data['websiteEn'], $data['website']);
            $job->getDescription()->updateValues($data['descriptionEn'], $data['description']);

            if (isset($data['labels'])) {
                $newLabels = array_map(
                    'intval',
                    $data['labels'],
                );
                $currentLabels = $job->getLabels()->map(static function ($label) {
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

                    if (null === $label) {
                        continue;
                    }

                    $job->addLabel($label);
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

            $updateProposal->setIsUpdate(true);

            $jobUpdateProposal = new JobUpdateProposalModel();
            $jobUpdateProposal->setOriginal($job);
            $jobUpdateProposal->setProposal($updateProposal);

            $this->jobMapper->persist($updateProposal);
            $this->jobUpdateMapper->persist($jobUpdateProposal);
            // TODO: Send e-mail to CEB/C4 about proposed changes.
        }

        $this->persistJob($job);

        return true;
    }

    /**
     * Deletes the given package.
     *
     * @throws ORMException
     */
    public function deletePackage(CompanyPackageModel $package): void
    {
        if (!$this->aclService->isAllowed('delete', 'package')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete packages'));
        }

        if ($package instanceof CompanyBannerPackageModel) {
            $this->bannerPackageMapper->remove($package);
        } elseif ($package instanceof CompanyFeaturedPackageModel) {
            $this->featuredPackageMapper->remove($package);
        } elseif ($package instanceof CompanyJobPackageModel) {
            $this->packageMapper->remove($package);
        }
    }

    /**
     * Deletes the given job and its associated data.
     *
     * @throws ORMException
     */
    public function deleteJob(JobModel $job): void
    {
        $this->deleteJobAttachments($job);

        /** @var JobUpdateProposalModel $jobUpdateProposal */
        foreach ($job->getUpdateProposals() as $jobUpdateProposal) {
            $this->deleteJobAttachments($jobUpdateProposal->getProposal());
        }

        $this->jobMapper->remove($job);
    }

    /**
     * Remove the actual attachments from a job.
     */
    private function deleteJobAttachments(JobModel $job): void
    {
        if (null !== ($dutchAttachment = $job->getAttachment()->getValueNL())) {
            $this->storageService->removeFile($dutchAttachment);
        }

        if (null === ($englishAttachment = $job->getAttachment()->getValueEN())) {
            return;
        }

        $this->storageService->removeFile($englishAttachment);
    }

    /**
     * Move jobs from an expired package to a non-expired package.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function transferJobs(array $data): bool
    {
        $newPackage = $this->packageMapper->find((int) $data['packages']);

        if (null === $newPackage) {
            return false;
        }

        foreach ($data['jobs'] as $formJob) {
            /** @var JobModel|null $job */
            $job = $this->jobMapper->find((int) $formJob);

            if (null === $job) {
                continue;
            }

            $job->setPackage($newPackage);
            $this->jobMapper->persist($job);
        }

        $this->jobMapper->flush();

        return true;
    }

    public function applyJobProposal(JobUpdateProposalModel $jobUpdate): void
    {
        $job = $jobUpdate->getOriginal();
        $data = $jobUpdate->getProposal()->toArray();

        // Fix some attributes.
        $data['category'] = $data['category']->getId();
        foreach ($data['labels'] as $key => $label) {
            $data['labels'][$key] = $label->getId();
        }

        $this->updateJob($job, $data, true);

        foreach ($job->getUpdateProposals() as $update) {
            // The proposed job is cascade deleted.
            $this->jobUpdateMapper->remove($update);
        }
    }

    public function cancelJobProposal(
        JobUpdateProposalModel $jobUpdate,
        string $message,
    ): void {
        $this->setJobApproval(
            $jobUpdate->getProposal(),
            ApprovableStatus::Rejected,
            $message,
        );
    }

    /**
     * Deletes the company identified with $slug.
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
     */
    public function getCompanyBySlugName(string $slugName): ?CompanyModel
    {
        return $this->companyMapper->findCompanyBySlugName($slugName);
    }

    /**
     * Returns a persistent category.
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
     */
    public function getPackageById(int $packageId): ?CompanyPackageModel
    {
        if (!$this->aclService->isAllowed('edit', 'package')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit packages'));
        }

        /** @var CompanyJobPackageModel|null $package */
        $package = $this->packageMapper->findEditablePackage($packageId);

        if (null === $package) {
            /** @var CompanyBannerPackageModel|null $package */
            $package = $this->bannerPackageMapper->findEditablePackage($packageId);
        }

        if (null === $package) {
            /** @var CompanyFeaturedPackageModel|null $package */
            $package = $this->featuredPackageMapper->findEditablePackage($packageId);
        }

        return $package;
    }

    /**
     * Returns all jobs with a given slugname, owned by a company with
     * $companySlugName.
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
     * @return EditPackageForm Form
     */
    public function getPackageForm(CompanyPackageTypes $type = CompanyPackageTypes::Job): EditPackageForm
    {
        if (CompanyPackageTypes::Banner === $type) {
            return $this->editBannerPackageForm;
        }

        if (CompanyPackageTypes::Featured === $type) {
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

    public function setJobApproval(
        JobModel $job,
        ApprovableStatus $status,
        ?string $message = null,
    ): void {
        $job->setApproved($status);
        $job->setApprovedAt(new DateTime());
        $job->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());

        if (
            null === $message
            || '' === $message
        ) {
            $message = null;
        } else {
            $message = trim($message);
            $message = new ApprovableTextModel($message);
        }

        $job->setApprovableText($message);

        $this->jobMapper->flush();
    }

    public function resetJobApproval(JobModel $job): void
    {
        $job->setApproved(ApprovableStatus::Unapproved);
        $job->setApprovedAt(null);
        $job->setApprover(null);
        // Orphans are automatically deleted, so we do not have to check if there is an actual approvable text.
        $job->setApprovableText(null);

        $this->jobMapper->flush();
    }
}
