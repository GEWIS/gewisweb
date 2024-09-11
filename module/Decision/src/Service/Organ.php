<?php

declare(strict_types=1);

namespace Decision\Service;

use Application\Model\Enums\ApprovableStatus;
use Application\Service\Email as EmailService;
use Application\Service\FileStorage as FileStorageService;
use DateTime;
use Decision\Form\OrganInformation as OrganInformationForm;
use Decision\Mapper\Member as MemberMapper;
use Decision\Mapper\Organ as OrganMapper;
use Decision\Mapper\OrganInformation as OrganInformationMapper;
use Decision\Model\Enums\OrganTypes;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Decision\Model\OrganInformation as OrganInformationModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Imagick;
use ImagickException;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

use function array_filter;
use function array_map;
use function array_merge;
use function array_search;
use function floatval;
use function getimagesize;
use function getrandmax;
use function min;
use function random_int;
use function round;
use function sys_get_temp_dir;
use function usort;

use const PHP_INT_MAX;

/**
 * User service.
 */
class Organ
{
    private const array FUNCTION_ORDER = [
        'Voorzitter',
        'Secretaris',
        'Penningmeester',
        'Vice-Voorzitter',
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly EntityManager $entityManager,
        private readonly FileStorageService $storageService,
        private readonly EmailService $emailService,
        private readonly MemberMapper $memberMapper,
        private readonly OrganInformationMapper $organInformationMapper,
        private readonly OrganMapper $organMapper,
        private readonly OrganInformationForm $organInformationForm,
        private readonly array $organInformationConfig,
    ) {
    }

    /**
     * Get organs.
     *
     * @return OrganModel[]
     */
    public function getOrgans(bool $abrogated = false): array
    {
        if (!$this->aclService->isAllowed('list', 'organ')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of organs'),
            );
        }

        if (!$abrogated) {
            return $this->organMapper->findActive();
        }

        return $this->organMapper->findAbrogated();
    }

    /**
     * Get one organ.
     */
    public function getOrgan(int $id): ?OrganModel
    {
        if (!$this->aclService->isAllowed('view', 'organ')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view organ information'),
            );
        }

        return $this->organMapper->findOrgan($id);
    }

    /**
     * Retrieves all organs which the current user is allowed to edit.
     *
     * @return OrganModel[]
     */
    public function getEditableOrgans(): array
    {
        if ($this->aclService->isAllowed('editall', 'organ')) {
            return array_merge(
                $this->findActiveOrgansByType(OrganTypes::Committee),
                $this->findActiveOrgansByType(OrganTypes::Fraternity),
                $this->findActiveOrgansByType(OrganTypes::AVC),
                $this->findActiveOrgansByType(OrganTypes::AVW),
                $this->findActiveOrgansByType(OrganTypes::KCC),
                $this->findActiveOrgansByType(OrganTypes::RvA),
            );
        }

        return $this->memberMapper->findOrgans($this->aclService->getUserIdentityOrThrowException()->getMember());
    }

    /**
     * Checks if the current user is allowed to use the given organ.
     */
    public function canUseOrgan(OrganModel $organ): bool
    {
        if ($this->aclService->isAllowed('editall', 'organ')) {
            return true;
        }

        return $this->memberMapper->isMemberOfOrgan(
            $this->aclService->getUserIdentityOrThrowException()->getMember(),
            $organ,
        );
    }

    /**
     * @param OrganTypes $type either committee, avc or fraternity
     *
     * @return OrganModel[]
     */
    public function findActiveOrgansByType(OrganTypes $type): array
    {
        return $this->organMapper->findActive($type);
    }

    public function findActiveOrganById(int $id): ?OrganModel
    {
        return $this->organMapper->findActiveById($id);
    }

    /**
     * @param OrganTypes $type either committee, avc or fraternity
     *
     * @return OrganModel[]
     */
    public function findAbrogatedOrgansByType(OrganTypes $type): array
    {
        return $this->organMapper->findAbrogated($type);
    }

    /**
     * Finds an organ by its abbreviation.
     *
     * @see Decision/Mapper/Organ::findByAbbr()
     *
     * @param bool $latest Whether to retrieve the latest occurrence of an organ or not
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findOrganByAbbr(
        string $abbr,
        ?OrganTypes $type = null,
        bool $latest = false,
    ): ?OrganModel {
        return $this->organMapper->findByAbbr(
            $abbr,
            $latest,
            $type,
        );
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function createOrganInformation(
        OrganModel $organ,
        array $data,
    ): ?OrganInformationModel {
        $form = $this->getOrganInformationForm();
        $form->setData($data);
        $form->bind(new OrganInformationModel());

        if (!$form->isValid()) {
            return null;
        }

        /** @var OrganInformationModel $organInformation */
        $organInformation = $form->getData();
        $organInformation->setOrgan($organ);

        $this->updateOrganInformationImages($organInformation, $data);

        // Automatically approve if a board member creates the organ information.
        if ($this->aclService->isAllowed('approve', 'organ')) {
            $organInformation->setApproved(ApprovableStatus::Approved);
            $organInformation->setApprovedAt(new DateTime());
            $organInformation->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());
        } else {
            $organInformation->setApproved(ApprovableStatus::Unapproved);

            $this->emailService->sendEmail(
                'organ_update',
                'email/organ-update',
                'Organ Profile Update',
                ['organ' => $organInformation->getOrgan()],
            );
        }

        $this->organInformationMapper->persist($organInformation);

        return $organInformation;
    }

    /**
     * @throws ORMException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function updateOrganInformation(
        OrganInformationModel $organInformation,
        array $data,
    ): bool {
        if ($this->aclService->isAllowed('approve', 'organ')) {
            $organInformation->setApproved(ApprovableStatus::Approved);
            $organInformation->setApprovedAt(new DateTime());
            $organInformation->setApprover($this->aclService->getUserIdentityOrThrowException()->getMember());
        } else {
            $organInformation = clone $organInformation;
            $organInformation->setApproved(ApprovableStatus::Unapproved);
            $organInformation->setIsUpdate(true);
        }

        // Update after the approval check to ensure we always have the correct `$organInformation`.
        $this->updateOrganInformationImages($organInformation, $data);

        $this->entityManager->flush();

        $this->emailService->sendEmail(
            'organ_update',
            'email/organ-update',
            'Organ Profile Update',
            ['organ' => $organInformation->getOrgan()],
        );

        return true;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function updateOrganInformationImages(
        OrganInformationModel $organInformation,
        array $data,
    ): void {
        $config = $this->organInformationConfig;

        if ($data['cover']['size'] > 0) {
            $coverPath = $this->generateOrganInformationImage(
                $data['cover']['tmp_name'],
                floatval($data['coverCropX']),
                floatval($data['coverCropY']),
                floatval($data['coverCropWidth']),
                floatval($data['coverCropHeight']),
                $config['cover_width'],
                $config['cover_height'],
            );

            $organInformation->setCoverPath($coverPath);
        }

        // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ($data['thumbnail']['size'] > 0) {
            $thumbnailPath = $this->generateOrganInformationImage(
                $data['thumbnail']['tmp_name'],
                floatval($data['thumbnailCropX']),
                floatval($data['thumbnailCropY']),
                floatval($data['thumbnailCropWidth']),
                floatval($data['thumbnailCropHeight']),
                $config['thumbnail_width'],
                $config['thumbnail_height'],
            );

            $organInformation->setThumbnailPath($thumbnailPath);
        }
    }

    /**
     * Create a thumbnail of the given file at the given location and scale.
     *
     * @param string $file        The file to create the thumbnail of
     * @param float  $x           The start x position in the image
     * @param float  $y           The start y position in the image
     * @param float  $width       The width of the area to crop
     * @param float  $height      The height of the area to crop
     * @param int    $thumbWidth  The width of the final thumbnail
     * @param int    $thumbHeight The height of the final thumbnail
     *
     * @return string path of where the thumbnail is stored
     *
     * @throws ImagickException
     */
    private function generateOrganInformationImage(
        string $file,
        float $x,
        float $y,
        float $width,
        float $height,
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
        $tempFileName = sys_get_temp_dir() . '/ThumbImage' . random_int(0, getrandmax()) . '.jpg';
        $image->writeImage($tempFileName);

        return $this->storageService->storeFile($tempFileName);
    }

    /**
     * Get the OrganInformation form.
     */
    public function getOrganInformationForm(?OrganModel $organ = null): OrganInformationForm
    {
        $form = $this->organInformationForm;

        if (null === $organ) {
            return $form;
        }

        // TODO: change this to be either the approved one or the latest update.
        $form->bind($organ->getApprovedOrganInformation());

        return $form;
    }

    /**
     * @throws ORMException
     */
    public function getEditableOrganInformation(OrganModel $organ): ?OrganInformationModel
    {
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
     * @return array{
     *     activeMembers: array<int, array{
     *         member: MemberModel,
     *         functions: string[],
     *     }>,
     *     inactiveMembers: array<int, MemberModel>,
     *     oldMembers: array<int, MemberModel>,
     * }
     */
    public function getOrganMemberInformation(OrganModel $organ): array
    {
        $now = new DateTime();
        $activeMembers = [];
        $inactiveMembers = [];
        $oldMembers = [];

        foreach ($organ->getMembers() as $install) {
            if (
                null === $install->getDischargeDate()
                || $install->getDischargeDate() > $now
            ) {
                // current member
                if ('Inactief Lid' === $install->getFunction()) {
                    // inactive
                    if (!isset($inactiveMembers[$install->getMember()->getLidnr()])) {
                        $inactiveMembers[$install->getMember()->getLidnr()] = $install->getMember();
                    }
                } else {
                    // active
                    if (!isset($activeMembers[$install->getMember()->getLidnr()])) {
                        $activeMembers[$install->getMember()->getLidnr()] = [
                            'member' => $install->getMember(),
                            'functions' => [],
                        ];
                    }

                    if ('Lid' !== $install->getFunction()) {
                        $activeMembers[$install->getMember()->getLidnr()]['functions'][] = $install->getFunction();
                    }
                }
            } else {
                // old member
                if (!isset($oldMembers[$install->getMember()->getLidnr()])) {
                    $oldMembers[$install->getMember()->getLidnr()] = $install->getMember();
                }
            }
        }

        $oldMembers = array_filter($oldMembers, static function ($member) use ($activeMembers, $inactiveMembers) {
            return !isset($activeMembers[$member->getLidnr()])
                && !isset($inactiveMembers[$member->getLidnr()]);
        });

        // Sort members by function
        usort(
            $activeMembers,
            static function ($a, $b) {
                $aFunctionPriorities = array_map(
                    static function ($function) {
                        return array_search($function, self::FUNCTION_ORDER);
                    },
                    $a['functions'],
                );

                $bFunctionPriorities = array_map(
                    static function ($function) {
                        return array_search($function, self::FUNCTION_ORDER);
                    },
                    $b['functions'],
                );

                $aHighestFunction = !empty($aFunctionPriorities) ? min($aFunctionPriorities) : PHP_INT_MAX;
                $bHighestFunction = !empty($bFunctionPriorities) ? min($bFunctionPriorities) : PHP_INT_MAX;

                return $aHighestFunction <=> $bHighestFunction;
            },
        );

        return [
            'activeMembers' => $activeMembers,
            'inactiveMembers' => $inactiveMembers,
            'oldMembers' => $oldMembers,
        ];
    }
}
