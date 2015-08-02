<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Model\Member as MemberModel;

/**
 * Member service.
 */
class Member extends AbstractAclService
{

    const MIN_SEARCH_QUERY_LENGTH = 2;

    /**
     * Obtain information about the current user.
     *
     * @return Decision\Model\Member
     */
    public function getMembershipInfo($lidnr = null)
    {
        if (null === $lidnr && !$this->isAllowed('view_self')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view membership info.')
            );
        } else if (null !== $lidnr && !$this->isAllowed('view')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view members.')
            );
        }

        if (null === $lidnr) {
            $lidnr = $this->getRole()->getLidnr();
        }

        $member = $this->getMemberMapper()->findByLidnr($lidnr);

        if (null === $member) {
            return null;
        }

        $memberships = array();
        foreach ($member->getOrganInstallations() as $install) {
            if (null !== $install->getDischargeDate()) {
                continue;
            }
            if (!isset($memberships[$install->getOrgan()->getAbbr()])) {
                $memberships[$install->getOrgan()->getAbbr()] = array();
            }
            if ($install->getFunction() != 'Lid') {
                $memberships[$install->getOrgan()->getAbbr()][] = $install->getFunction();
            }
        }

        $tags = $this->getPhotoService()->getTagsForMember($member);
        // Base directory for retrieving photos
        $basedir = $this->getPhotoService()->getBaseDirectory();

        return array(
            'member' => $member,
            'memberships' => $memberships,
            'tags' => $tags,
            'basedir' => $basedir
        );
    }

    /**
     *
     */
    public function findMemberByLidNr($lidnr)
    {
        return $this->getMemberMapper()->findByLidnr($lidnr);
    }

    /**
     * Get the members of which their birthday falls in the next $days days.
     *
     * When $days equals 0 or isn't given, it will give all birthdays of today.
     *
     * @param int $days The number of days to look ahead.
     *
     * @return array Of members sorted by birthday
     */
    public function getBirthdayMembers($days = 0)
    {
        if (!$this->isAllowed('birthdays')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the list of birthdays.')
            );
        }
        return $this->getMemberMapper()->findBirthdayMembers($days);
    }

    /**
     * Get the organs a member is part of.
     *
     * @param MemberModel $member
     *
     * @return array
     */
    public function getOrgans(MemberModel $member)
    {
        return $this->getMemberMapper()->findOrgans($member);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param string $query (part of) the full name of a member
     * @pre $name must be at least MIN_SEARCH_QUERY_LENGTH
     *
     * @return array|null
     */
    public function searchMembersByName($query)
    {
        if (strlen($query) < self::MIN_SEARCH_QUERY_LENGTH) {
            throw new \Zend\Code\Exception\InvalidArgumentException(
                $this->getTranslator()->translate('Name must be at least ' . self::MIN_SEARCH_QUERY_LENGTH . ' characters')
            );
        }

        if (!$this->isAllowed('search')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to search for members.')
            );
        }

        return $this->getMemberMapper()->searchByName($query);

    }

    /**
     * Get the member mapper.
     *
     * @return \Decision\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->sm->get('decision_mapper_member');
    }


    /**
     * Get the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->sm->get('photo_service_photo');
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
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('decision_acl');
    }
}
