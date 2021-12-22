<?php

namespace Decision\Service;

use ImagickException;
use Doctrine\ORM\{
    NonUniqueResultException,
    NoResultException,
    ORMException,
    EntityManager,
};
use Application\Service\{
    Email as EmailService,
    FileStorage as FileStorageService,
};
use Decision\Form\OrganInformation as OrganInformationForm;
use Decision\Mapper\{
    Organ as OrganMapper,
    Member as MemberMapper,
};
use Decision\Model\{
    Organ as OrganModel,
    OrganInformation as OrganInformationModel,
};
use Imagick;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

/**
 * User service.
 */
class Organ
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var FileStorageService
     */
    private FileStorageService $storageService;

    /**
     * @var EmailService
     */
    private EmailService $emailService;

    /**
     * @var MemberMapper
     */
    private MemberMapper $memberMapper;

    /**
     * @var OrganMapper
     */
    private OrganMapper $organMapper;

    /**
     * @var OrganInformationForm
     */
    private OrganInformationForm $organInformationForm;

    /**
     * @var array
     */
    private array $organInformationConfig;

    /**
     * @param AclService $aclService
     * @param Translator $translator
     * @param EntityManager $entityManager
     * @param FileStorageService $storageService
     * @param EmailService $emailService
     * @param MemberMapper $memberMapper
     * @param OrganMapper $organMapper
     * @param OrganInformationForm $organInformationForm
     * @param array $organInformationConfig
     */
    public function __construct(
        AclService $aclService,
        Translator $translator,
        EntityManager $entityManager,
        FileStorageService $storageService,
        EmailService $emailService,
        MemberMapper $memberMapper,
        OrganMapper $organMapper,
        OrganInformationForm $organInformationForm,
        array $organInformationConfig,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->storageService = $storageService;
        $this->emailService = $emailService;
        $this->memberMapper = $memberMapper;
        $this->organMapper = $organMapper;
        $this->organInformationForm = $organInformationForm;
        $this->organInformationConfig = $organInformationConfig;
    }

    /**
     * Get organs.
     *
     * @return array of organs
     */
    public function getOrgans(): array
    {
        if (!$this->aclService->isAllowed('list', 'organ')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view the list of organs'));
        }

        return $this->organMapper->findActive();
    }

    /**
     * Get one organ.
     *
     * @param int $id
     *
     * @return OrganModel|null
     */
    public function getOrgan(int $id): ?OrganModel
    {
        if (!$this->aclService->isAllowed('view', 'organ')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view organ information'));
        }

        return $this->organMapper->findOrgan($id);
    }

    /**
     * Retrieves all organs which the current user is allowed to edit.
     *
     * @return array
     */
    public function getEditableOrgans(): array
    {
        if (!$this->aclService->isAllowed('edit', 'organ')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit organ information')
            );
        }

        if ($this->aclService->isAllowed('editall', 'organ')) {
            return array_merge(
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_COMMITTEE),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_FRATERNITY),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_AVC),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_AVW),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_KKK),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_RVA)
            );
        }

        $user = $this->aclService->getIdentityOrThrowException();

        return $this->memberMapper->findOrgans($user->getMember());
    }

    /**
     * Checks if the current user is allowed to edit the given organ.
     *
     * @param OrganModel $organ
     *
     * @return bool
     */
    public function canEditOrgan(OrganModel $organ): bool
    {
        if (!$this->aclService->isAllowed('edit', 'organ')) {
            return false;
        }

        if ($this->aclService->isAllowed('editall', 'organ')) {
            return true;
        }

        $user = $this->aclService->getIdentityOrThrowException();

        foreach ($this->memberMapper->findOrgans($user->getMember()) as $memberOrgan) {
            if ($memberOrgan->getId() === $organ->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $type either committee, avc or fraternity
     *
     * @return array
     */
    public function findActiveOrgansByType(string $type): array
    {
        return $this->organMapper->findActive($type);
    }

    /**
     * @param int $id
     *
     * @return OrganModel|null
     */
    public function findActiveOrganById(int $id): ?OrganModel
    {
        return $this->organMapper->findActiveById($id);
    }

    /**
     * @param string $type either committee, avc or fraternity
     *
     * @return array
     */
    public function findAbrogatedOrgansByType(string $type): array
    {
        return $this->organMapper->findAbrogated($type);
    }

    /**
     * Finds an organ by its abbreviation.
     *
     * @param string $abbr
     * @param string|null $type
     * @param bool $latest  Whether to retrieve the latest occurrence of an organ or not
     *
     * @return OrganModel|null
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @see Decision/Mapper/Organ::findByAbbr()
     */
    public function findOrganByAbbr(
        string $abbr,
        ?string $type = null,
        bool $latest = false,
    ): ?OrganModel {
        return $this->organMapper->findByAbbr(
            $abbr,
            $latest,
            $type,
        );
    }

    /**
     * @param OrganInformationModel $organInformation
     * @param array $data
     *
     * @return bool
     * @throws ORMException
     * @throws ImagickException
     */
    public function updateOrganInformation(
        OrganInformationModel $organInformation,
        array $data,
    ): bool {
        $config = $this->organInformationConfig;

        if ($data['cover']['size'] > 0) {
            $coverPath = $this->makeOrganInformationImage(
                $data['cover']['tmp_name'],
                $data['coverCropX'],
                $data['coverCropY'],
                $data['coverCropWidth'],
                $data['coverCropHeight'],
                $config['cover_width'],
                $config['cover_height']
            );

            $organInformation->setCoverPath($coverPath);
        }

        if ($data['thumbnail']['size'] > 0) {
            $thumbnailPath = $this->makeOrganInformationImage(
                $data['thumbnail']['tmp_name'],
                $data['thumbnailCropX'],
                $data['thumbnailCropY'],
                $data['thumbnailCropWidth'],
                $data['thumbnailCropHeight'],
                $config['thumbnail_width'],
                $config['thumbnail_height']
            );

            $organInformation->setThumbnailPath($thumbnailPath);
        }

        $this->entityManager->flush();

        if ($this->aclService->isAllowed('approve', 'organ')) {
            $this->approveOrganInformation($organInformation);
        }

        $this->emailService->sendEmail(
            'organ_update',
            'email/organUpdate',
            'Een orgaan heeft een update doorgevoerd | An organ has updated her page',
            ['organInfo' => $organInformation]
        );

        return true;
    }

    /**
     * Create a thumbnail of the given file at the given location and scale.
     *
     * @param string $file The file to create the thumbnail of
     * @param string $x The start x position in the image
     * @param string $y The start y position in the image
     * @param string $width The width of the area to crop
     * @param string $height The height of the are to crop
     * @param int $thumbWidth The width of the final thumbnail
     * @param int $thumbHeight The height of the final thumbnail
     *
     * @return string path of where the thumbnail is stored
     * @throws ImagickException
     */
    public function makeOrganInformationImage(
        string $file,
        string $x,
        string $y,
        string $width,
        string $height,
        int $thumbWidth,
        int $thumbHeight,
    ): string {
        $size = getimagesize($file);
        $x = (int) round($x * $size[0]);
        $y = (int) round($y * $size[1]);
        $width = (int) round($width * $size[0]);
        $height = (int) round($height * $size[1]);

        $image = new Imagick($file);
        $image->cropImage($width, $height, $x, $y);
        $image->thumbnailImage($thumbWidth, $thumbHeight);
        $image->setimageformat('jpg');

        //Tempfile is used such that the file storage service can generate a filename
        $tempFileName = sys_get_temp_dir() . '/ThumbImage' . rand() . '.jpg';
        $image->writeImage($tempFileName);

        return $this->storageService->storeFile($tempFileName);
    }

    /**
     * @param OrganInformationModel $organInformation
     *
     * @throws ORMException
     */
    public function approveOrganInformation(OrganInformationModel $organInformation): void
    {
        $em = $this->entityManager;
        $oldInformation = $organInformation->getOrgan()->getApprovedOrganInformation();

        if (null !== $oldInformation) {
            $em->remove($oldInformation);
        }

        $user = $this->aclService->getIdentityOrThrowException();
        $organInformation->setApprover($user);
        $em->flush();
    }

    /**
     * Get the OrganInformation form.
     *
     *
     * @param OrganInformationModel $organInformation
     *
     * @return OrganInformationForm
     */
    public function getOrganInformationForm(OrganInformationModel $organInformation): OrganInformationForm
    {
        if (!$this->canEditOrgan($organInformation->getOrgan())) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit this organ\'s information')
            );
        }

        $form = $this->organInformationForm;
        $form->bind($organInformation);

        return $form;
    }

    /**
     * @param int $organId
     *
     * @return OrganInformationModel|bool
     *
     * @throws ORMException
     */
    public function getEditableOrganInformation(int $organId): OrganInformationModel|bool
    {
        $organ = $this->getOrgan($organId); //TODO: catch exception

        if (null === $organ) {
            return false;
        }

        $em = $this->entityManager;
        $organInformation = null;

        foreach ($organ->getOrganInformation() as $information) {
            if (null === $information->getApprover()) {
                return $information;
            }

            $organInformation = $information;
        }

        if (null === $organInformation) {
            $organInformation = new OrganInformationModel();
            $organInformation->setOrgan($organ);
            $em->persist($organInformation);
            $organ->getOrganInformation()->add($organInformation);

            return $organInformation;
        }

        /*
         * Create an unapproved clone of the organ information
         */
        $organInformation = clone $organInformation;
        $organInformation->setApprover(null);
        $em->detach($organInformation);
        $em->persist($organInformation);
        $organ->getOrganInformation()->add($organInformation);

        return $organInformation;
    }

    /**
     * Returns a list of an organ's current and previous members including their function.
     *
     * @param OrganModel $organ
     *
     * @return array
     */
    public function getOrganMemberInformation(OrganModel $organ): array
    {
        $oldMembers = [];
        $currentMembers = [];
        foreach ($organ->getMembers() as $install) {
            if (null === $install->getDischargeDate()) {
                // current member
                if (!isset($currentMembers[$install->getMember()->getLidnr()])) {
                    $currentMembers[$install->getMember()->getLidnr()] = [
                        'member' => $install->getMember(),
                        'functions' => [],
                    ];
                }

                if ('Lid' != $install->getFunction()) {
                    $function = $this->translator->translate($install->getFunction());
                    $currentMembers[$install->getMember()->getLidnr()]['functions'][] = $function;
                }
            } else {
                // old member
                if (!isset($oldMembers[$install->getMember()->getLidnr()])) {
                    $oldMembers[$install->getMember()->getLidnr()] = $install->getMember();
                }
            }
        }

        $oldMembers = array_filter($oldMembers, function ($member) use ($currentMembers) {
            return !isset($currentMembers[$member->getLidnr()]);
        });

        // Sort members by function
        usort($currentMembers, function ($a, $b) {
            if ($a['functions'] == $b['functions']) {
                return 0;
            }

            if (count($a['functions']) > count($b['functions'])) {
                return -1;
            }

            return in_array('Voorzitter', $a['functions']) ? -1 : 1;
        });

        return [
            'oldMembers' => $oldMembers,
            'currentMembers' => $currentMembers,
        ];
    }
}
