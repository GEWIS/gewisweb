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
        $birthdays = $this->getBirthdays();
        return array(
            'birthdays' => $birthdays
        );
    }

    /**
     * Retrieves all birthdays happening today, which should be shown on the home page.
     * Includes the age.
     */
    public function getBirthdays()
    {
        $birthdayMembers = $this->getMemberService()->getBirthdayMembers();
        $today = new \DateTime();
        $birthdays = array();
        foreach($birthdayMembers as $member) {
            $age = $today->diff($member->getBirth())->y;
            //TODO: check member's privacy settings
            $birthdays[] = array('member' => $member, 'age' => $age);
        }

        return $birthdays;
    }

    /**
     * Retrieves a photo of a member whom has a birthday.
     */
    public function getBirthdayPhoto($birthdays)
    {

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
