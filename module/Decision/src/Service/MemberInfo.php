<?php

namespace Decision\Service;

use Decision\Mapper\Member as MemberMapper;
use Decision\Model\Member as MemberModel;
use Exception;
use Laminas\Mvc\I18n\Translator;
use Photo\Service\Photo as PhotoService;
use User\Permissions\NotAllowedException;

/**
 * Member service.
 */
class MemberInfo
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var PhotoService
     */
    private PhotoService $photoService;

    /**
     * @var MemberMapper
     */
    private MemberMapper $memberMapper;

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var array
     */
    private array $photoConfig;

    public function __construct(
        Translator $translator,
        PhotoService $photoService,
        MemberMapper $memberMapper,
        AclService $aclService,
        array $photoConfig,
    ) {
        $this->translator = $translator;
        $this->photoService = $photoService;
        $this->memberMapper = $memberMapper;
        $this->aclService = $aclService;
        $this->photoConfig = $photoConfig;
    }

    /**
     * Obtain information about the current user.
     *
     * @param int|null $lidnr
     *
     * @return array|null
     * @throws Exception
     */
    public function getMembershipInfo(?int $lidnr = null): ?array
    {
        if (null === $lidnr && !$this->aclService->isAllowed('view_self', 'member')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view membership info.'));
        } elseif (null !== $lidnr && !$this->aclService->isAllowed('view', 'member')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view members.'));
        }

        if (null === $lidnr) {
            $lidnr = $this->aclService->getIdentityOrThrowException()->getLidnr();
        }

        $member = $this->memberMapper->findByLidnr($lidnr);

        if (null === $member) {
            return null;
        }

        $memberships = $this->getOrganMemberships($member);

        $tags = $this->photoService->getTagsForMember($member);

        // Base directory for retrieving photos
        $basedir = $this->photoService->getBaseDirectory();

        $profilePhoto = $this->photoService->getProfilePhoto($lidnr);
        $isExplicitProfilePhoto = $this->photoService->hasExplicitProfilePhoto($lidnr);
        $photoConfig = $this->photoConfig;

        return [
            'member' => $member,
            'memberships' => $memberships,
            'tags' => $tags,
            'profilePhoto' => $profilePhoto,
            'isExplicitProfilePhoto' => $isExplicitProfilePhoto,
            'basedir' => $basedir,
            'photoConfig' => $photoConfig,
        ];
    }

    /**
     * Gets a list of all organs which the member currently is part of.
     *
     * @param MemberModel $member
     *
     * @return array
     */
    public function getOrganMemberships(MemberModel $member): array
    {
        $memberships = [];
        foreach ($member->getOrganInstallations() as $install) {
            if (null !== $install->getDischargeDate()) {
                continue;
            }

            if (!isset($memberships[$install->getOrgan()->getAbbr()])) {
                $memberships[$install->getOrgan()->getAbbr()] = [];
                $memberships[$install->getOrgan()->getAbbr()]['organ'] = $install->getOrgan();
            }

            if ('Lid' != $install->getFunction()) {
                $function = $this->translator->translate($install->getFunction());
                $memberships[$install->getOrgan()->getAbbr()]['functions'] = $function;
            }
        }

        return $memberships;
    }
}
