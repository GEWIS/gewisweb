<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;
use Application\Service\Email;
use Application\Service\FileStorage;
use Decision\Mapper\Organ as OrganMapper;
use Decision\Model\Organ as OrganModel;
use Decision\Model\OrganInformation;
use Doctrine\ORM\EntityManager;
use Imagick;
use User\Permissions\NotAllowedException;
use User\Service\User;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;

/**
 * User service.
 */
class Organ extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var \User\Model\User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var User
     */
    private $userService;

    /**
     * @var FileStorage
     */
    private $storageService;

    /**
     * @var Email
     */
    private $emailService;

    /**
     * @var \Decision\Mapper\Member
     */
    private $memberMapper;

    /**
     * @var OrganMapper
     */
    private $organMapper;

    /**
     * @var \Decision\Form\OrganInformation
     */
    private $organInformationForm;

    /**
     * @var array
     */
    private $organInformationConfig;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        EntityManager $entityManager,
        User $userService,
        FileStorage $storageService,
        Email $emailService,
        \Decision\Mapper\Member $memberMapper,
        OrganMapper $organMapper,
        \Decision\Form\OrganInformation $organInformationForm,
        array $organInformationConfig
    )
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->storageService = $storageService;
        $this->emailService = $emailService;
        $this->memberMapper = $memberMapper;
        $this->organMapper = $organMapper;
        $this->organInformationForm = $organInformationForm;
        $this->organInformationConfig = $organInformationConfig;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get organs.
     *
     * @return array Of organs.
     */
    public function getOrgans()
    {
        if (!$this->isAllowed('list')) {
            throw new NotAllowedException(
                $this->translator->translate('Not allowed to view the list of organs.')
            );
        }

        return $this->organMapper->findActive();
    }

    /**
     * Get one organ.
     *
     * @param int $id
     *
     * @return OrganModel
     */
    public function getOrgan($id)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException(
                $this->translator->translate('Not allowed to view organ information')
            );
        }

        return $this->organMapper->find($id);
    }

    /**
     * Retrieves all organs which the current user is allowed to edit.
     *
     * @return array
     */
    public function getEditableOrgans()
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit organ information')
            );
        }

        if ($this->isAllowed('editall')) {
            return array_merge(
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_COMMITTEE),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_FRATERNITY),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_AVC),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_AVW),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_KKK),
                $this->findActiveOrgansByType(OrganModel::ORGAN_TYPE_RVA)
            );
        }

        $user = $this->userService->getIdentity();

        return $this->memberMapper->findOrgans($user->getMember());
    }

    /**
     * Checks if the current user is allowed to edit the given organ.
     *
     * @return bool
     */
    public function canEditOrgan($organ)
    {
        if (!$this->isAllowed('edit')) {
            return false;
        }

        if ($this->isAllowed('editall')) {
            return true;
        }

        $user = $this->userService->getIdentity();

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
    public function findActiveOrgansByType($type)
    {
        return $this->organMapper->findActive($type);
    }

    /**
     * @param string $type either committee, avc or fraternity
     *
     * @return array
     */
    public function findAbrogatedOrgansByType($type)
    {
        return $this->organMapper->findAbrogated($type);
    }

    /**
     * Finds an organ by its abbreviation.
     *
     * @param $abbr
     * @param string $type
     * @param bool $latest
     *    Whether to retrieve the latest occurence of an organ or not.
     *
     * @return OrganModel
     * @see Decision/Mapper/Organ::findByAbbr()
     *
     */
    public function findOrganByAbbr($abbr, $type = null, $latest = false)
    {
        return $this->organMapper->findByAbbr($abbr, $type, $latest);
    }

    /**
     * @param integer $organId
     *
     * @param array $post POST Data
     * @param array $files FILES Data
     *
     * @return bool
     */
    public function updateOrganInformation($organId, $post, $files)
    {
        $organInformation = $this->getEditableOrganInformation($organId);
        $form = $this->getOrganInformationForm($organInformation);

        $data = array_merge_recursive($post->toArray(), $files->toArray());

        $form->setData($data);
        if (!$form->isValid()) {
            return false;
        }

        $config = $this->organInformationConfig;
        if ($files['cover']['size'] > 0) {
            $coverPath = $this->makeOrganInformationImage(
                $files['cover']['tmp_name'],
                $data['coverCropX'],
                $data['coverCropY'],
                $data['coverCropWidth'],
                $data['coverCropHeight'],
                $config['cover_width'],
                $config['cover_height']
            );
            $organInformation->setCoverPath($coverPath);
        }

        if ($files['thumbnail']['size'] > 0) {
            $thumbnailPath = $this->makeOrganInformationImage(
                $files['thumbnail']['tmp_name'],
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

        if ($this->isAllowed('approve')) {
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
     * @return string path of where the thumbnail is stored
     */
    public function makeOrganInformationImage($file, $x, $y, $width, $height, $thumbWidth, $thumbHeight)
    {
        $size = getimagesize($file);
        $x = round($x * $size[0]);
        $y = round($y * $size[1]);
        $width = round($width * $size[0]);
        $height = round($height * $size[1]);

        $image = new Imagick($file);
        $image->cropImage($width, $height, $x, $y);
        $image->thumbnailImage($thumbWidth, $thumbHeight);
        $image->setimageformat('jpg');

        //Tempfile is used such that the file storage service can generate a filename
        $tempFileName = sys_get_temp_dir() . '/ThumbImage' . rand() . '.jpg';
        $image->writeImage($tempFileName);

        return $this->storageService->storeFile($tempFileName);
    }

    public function approveOrganInformation($organInformation)
    {
        $em = $this->entityManager;
        $oldInformation = $organInformation->getOrgan()->getApprovedOrganInformation();
        if (!is_null($oldInformation)) {
            $em->remove($oldInformation);
        }
        $user = $em->merge($this->userService->getIdentity());
        $organInformation->setApprover($user);
        $em->flush();
    }

    /**
     * Get the OrganInformation form.
     *
     * @param OrganInformation $organInformation
     *
     * @return \Decision\Form\OrganInformation|bool
     */
    public function getOrganInformationForm($organInformation)
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

    public function getEditableOrganInformation($organId)
    {
        $organ = $this->getOrgan($organId); //TODO: catch exception
        if (is_null($organ)) {
            return false;
        }
        $em = $this->entityManager;
        $organInformation = null;
        foreach ($organ->getOrganInformation() as $information) {
            if (is_null($information->getApprover())) {
                return $information;
            }
            $organInformation = $information;
        }

        if (is_null($organInformation)) {
            $organInformation = new OrganInformation();
            // TODO: ->setOrgan is undefined
            $organInformation->setOrgan($organ);
            $em->persist($organInformation);
            // TODO: ->add is undefined
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
        // TODO: ->add is undefined
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
    public function getOrganMemberInformation($organ)
    {
        $oldMembers = [];
        $currentMembers = [];
        foreach ($organ->getMembers() as $install) {
            if (null === $install->getDischargeDate()) {
                // current member
                if (!isset($currentMembers[$install->getMember()->getLidnr()])) {
                    $currentMembers[$install->getMember()->getLidnr()] = [
                        'member' => $install->getMember(),
                        'functions' => []
                    ];
                }
                if ($install->getFunction() != 'Lid') {
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
            'currentMembers' => $currentMembers
        ];
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'organ';
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
}
