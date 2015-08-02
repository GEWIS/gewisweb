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
            'birthdayPhoto' => $birthdayInfo['photo']
        );
    }

    /**
     * Retrieves all birthdays happening today, which should be shown on the home page.
     * Includes the age and a random photo of someone whom has a birthday.
     *
     * @return array
     */
    public function getBirthdayInfo()
    {
        $birthdayMembers = $this->getMemberService()->getBirthdayMembers();
        $today = new \DateTime();
        $birthdays = array();
        $photos = array();
        foreach($birthdayMembers as $member) {
            $age = $today->diff($member->getBirth())->y;
            $photos[] = $this->getMemberPhoto($member);
                //TODO: check member's privacy settings
            $birthdays[] = array('member' => $member, 'age' => $age);

        }

        $k = array_rand($photos);
        $photo = $photos[$k];

        return array(
            'birthdays' => $birthdays,
            'photo' => $photo
        );
    }

    /**
     * Retrieves a random photo of a member.
     *
     * @param \Decision\Model\Member $member
     *
     * @return \Photo\Model\Photo|null
     */
    public function getMemberPhoto($member)
    {
        $tag = $this->getTagMapper()->getRandomMemberTag($member);
        if(is_null($tag)) {
            return null;
        } else {
            return $tag->getPhoto();
        }
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
