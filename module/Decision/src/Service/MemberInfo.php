<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;
use Decision\Model\Member as MemberModel;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use Photo\Service\Photo;
use User\Model\User;
use User\Permissions\NotAllowedException;

/**
 * Member service.
 */
class MemberInfo extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var Photo
     */
    private $photoService;

    /**
     * @var \Decision\Mapper\Member
     */
    private $memberMapper;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        Photo $photoService,
        \Decision\Mapper\Member $memberMapper
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->photoService = $photoService;
        $this->memberMapper = $memberMapper;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Obtain information about the current user.
     *
     * @return array|null
     */
    public function getMembershipInfo($lidnr = null)
    {
        if (null === $lidnr && !$this->isAllowed('view_self')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view membership info.'));
        } elseif (null !== $lidnr && !$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view members.'));
        }

        if (null === $lidnr) {
            $lidnr = $this->getRole()->getLidnr();
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

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'member';
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
