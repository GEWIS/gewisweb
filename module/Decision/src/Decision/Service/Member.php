<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Application\Service\FileStorage;
use Decision\Mapper\Authorization;
use Decision\Model\Member as MemberModel;

use Photo\Service\Photo;
use User\Permissions\NotAllowedException;
use Zend\Code\Exception\InvalidArgumentException;
use Zend\Http\Client as HttpClient;
use Zend\Mvc\I18n\Translator;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

/**
 * Member service.
 */
class Member extends AbstractAclService implements ServiceManagerAwareInterface
{

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

    /**
     * Set the service manager.
     *
     * @param ServiceManager $sm
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    public function getRole()
    {
        return $this->sm->get('user_role');
    }
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    const MIN_SEARCH_QUERY_LENGTH = 2;

    /**
     * Obtain information about the current user.
     *
     * @return MemberModel
     */
    public function getMembershipInfo($lidnr = null)
    {
        if (null === $lidnr && !$this->isAllowed('view_self')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view membership info.')
            );
        } elseif (null !== $lidnr && !$this->isAllowed('view')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view members.')
            );
        }

        if (null === $lidnr) {
            $lidnr = $this->getRole()->getLidnr();
        }

        $member = $this->getMemberMapper()->findByLidnr($lidnr);

        if (null === $member) {
            return null;
        }

        $memberships = $this->getOrganMemberships($member);

        $tags = $this->getPhotoService()->getTagsForMember($member);

        // Base directory for retrieving photos
        $basedir = $this->getPhotoService()->getBaseDirectory();

        $photoService = $this->getPhotoService();
        $profilePhoto = $photoService->getProfilePhoto($lidnr);
        $isExplicitProfilePhoto = $photoService->hasExplicitProfilePhoto($lidnr);

        return [
            'member' => $member,
            'memberships' => $memberships,
            'tags' => $tags,
            'profilePhoto' => $profilePhoto,
            'isExplicitProfilePhoto' => $isExplicitProfilePhoto,
            'basedir' => $basedir
        ];
    }

    /**
     * Gets a list of all organs which the member currently is part of
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
            if ($install->getFunction() != 'Lid') {
                $function = $this->translator->translate($install->getFunction());
                $memberships[$install->getOrgan()->getAbbr()]['functions'] = $function;
            }
        }
        return $memberships;
    }

    /**
     * Returns is the member is active
     *
     * @param MemberModel $member
     * @return bool
     */

    public function isActiveMember()
    {
        return $this->isAllowed('edit', 'organ');
    }

    /**
     *
     */
    public function findMemberByLidNr($lidnr)
    {
        if (!$this->isAllowed('view')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view members.')
            );
        }

        return $this->getMemberMapper()->findByLidnr($lidnr);
    }

    /**
     * Get the dreamspark URL for the current user.
     */
    public function getDreamsparkUrl()
    {
        if (!$this->isAllowed('login', 'dreamspark')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed login into Microsoft Imagine.')
            );
        }

        $user = $this->sm->get('user_service_user')->getIdentity();

        $config = $this->sm->get('config');
        $sslcapath = $config['sslcapath'];
        $config = $config['dreamspark'];

        // determine groups for dreamspark
        $groups = [];
        if ($this->isAllowed('students', 'dreamspark')) {
            $groups[] = 'students';
        }
        if ($this->isAllowed('faculty', 'dreamspark')) {
            $groups[] = 'faculty';
        }
        if ($this->isAllowed('staff', 'dreamspark')) {
            $groups[] = 'staff';
        }

        $url = $config['url'];
        $url = str_replace('%ACCOUNT%', $config['account'], $url);
        $url = str_replace('%KEY%', $config['key'], $url);
        $url = str_replace('%EMAIL%', $user->getEmail(), $url);
        $url = str_replace('%GROUPS%', implode(',', $groups), $url);

        $client = new HttpClient($url, [
            'sslcapath' => $sslcapath
        ]);
        $response = $client->send();

        if ($response->getStatusCode() != 200) {

            throw new NotAllowedException(
                $this->translator->translate('Login to Microsoft Imagine failed. If this persists, contact the WebCommittee.')
            );
        }

        return $response->getBody();
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
        if ($days == 0 && !$this->isAllowed('birthdays_today')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of today\'s birthdays.')
            );
        }

        if ($days > 0 && !$this->isAllowed('birthdays')) {

            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of birthdays.')
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
            throw new InvalidArgumentException(
                $this->translator->translate('Name must be at least ' . self::MIN_SEARCH_QUERY_LENGTH . ' characters')
            );
        }

        if (!$this->isAllowed('search')) {
            throw new NotAllowedException(
                $this->translator->translate('Not allowed to search for members.')
            );
        }

        return $this->getMemberMapper()->searchByName($query);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param string $query (part of) the full name of a member
     * @pre $name must be at least MIN_SEARCH_QUERY_LENGTH
     *
     * @return array|null
     */
    public function canAuthorize($member, $meeting)
    {
        $maxAuthorizations = 2;

        $authorizationMapper = $this->getAuthorizationMapper();
        $meetingNumber = $meeting->getNumber();
        $lidnr = $member->getLidnr();
        $authorizations = $authorizationMapper->findRecipientAuthorization($meetingNumber, $lidnr);

        if (count($authorizations) < $maxAuthorizations) {
            return true;
        }
        return false;
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
     * @return Photo
     */
    public function getPhotoService()
    {
        return $this->sm->get('photo_service_photo');
    }

    /**
     * Get the photo service.
     *
     * @return FileStorage
     */
    public function getFileStorageService()
    {
        return $this->sm->get('application_service_storage');
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
        return $this->sm->get('decision_acl');
    }

    /**
     * Get the authorization mapper.
     *
     * @return Authorization
     */
    public function getAuthorizationMapper()
    {
        return $this->sm->get('decision_mapper_authorization');
    }
}
