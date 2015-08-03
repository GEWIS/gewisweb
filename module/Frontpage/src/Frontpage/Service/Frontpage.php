<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;

/**
 * Frontpage service.
 */
class Frontpage extends AbstractAclService
{

    /**
     * Retrieves all data which is needed on the home page
     */
    public function getHomePageData()
    {
        $birthdayInfo = $this->getBirthdayInfo();
        return array(
            'birthdays' => $birthdayInfo['birthdays'],
            'birthdayTag' => $birthdayInfo['tag']
        );
    }

    /**
     * Retrieves all birthdays happening today, which should be shown on the home page.
     * Includes the age and a recent tag of the most active member whom has a birthday.
     *
     * @return array
     */
    public function getBirthdayInfo()
    {
        $birthdayMembers = $this->getMemberService()->getBirthdayMembers();
        $today = new \DateTime();
        $birthdays = array();
        $members = array();
        foreach($birthdayMembers as $member) {
            $age = $today->diff($member->getBirth())->y;
            $members[] = $member;
                //TODO: check member's privacy settings
            $birthdays[] = array('member' => $member, 'age' => $age);

        }

        $tag = $this->getTagMapper()->getMostActiveMemberTag($members);

        return array(
            'birthdays' => $birthdays,
            'tag' => $tag
        );
    }

    /**
     * Get the frontpage config, as used by this service.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');
        return $config['frontpage'];
    }

    /**
     * Get the photo module's tag mapper.
     *
     * @return \Photo\Mapper\Tag
     */
    public function getTagMapper()
    {
        return $this->sm->get('photo_mapper_tag');
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
     * Get the member service.
     *
     * @return \Decision\Service\Member
     */
    public function getMemberService()
    {
        return $this->sm->get('Decision_service_member');
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('frontpage_acl');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'frontpage';
    }
}
