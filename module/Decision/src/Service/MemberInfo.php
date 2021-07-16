<?php

namespace Decision\Service;

use Decision\Model\Member as MemberModel;
use Laminas\Mvc\I18n\Translator;
use Photo\Service\Photo;
use User\Permissions\NotAllowedException;

/**
 * Member service.
 */
class MemberInfo
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Photo
     */
    private $photoService;

    /**
     * @var \Decision\Mapper\Member
     */
    private $memberMapper;
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        Photo $photoService,
        \Decision\Mapper\Member $memberMapper,
        AclService $aclService
    ) {
        $this->translator = $translator;
        $this->photoService = $photoService;
        $this->memberMapper = $memberMapper;
        $this->aclService = $aclService;
    }

    /**
     * Obtain information about the current user.
     *
     * @return array|null
     */
    public function getMembershipInfo($lidnr = null)
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

        return [
            'member' => $member,
            'memberships' => $memberships,
            'tags' => $tags,
            'profilePhoto' => $profilePhoto,
            'isExplicitProfilePhoto' => $isExplicitProfilePhoto,
            'basedir' => $basedir,
        ];
    }

    /**
     * Gets a list of all organs which the member currently is part of.
     *
     * @param MemberModel $member
     *
     * @return array
     */
    public function getOrganMemberships($member)
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
