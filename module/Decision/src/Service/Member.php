<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;
use Decision\Mapper\Authorization;
use Decision\Model\Meeting;
use Decision\Model\Member as MemberModel;
use Doctrine\Common\Collections\Collection;
use Laminas\Code\Exception\InvalidArgumentException;
use Laminas\Http\Client as HttpClient;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use User\Permissions\NotAllowedException;
use User\Service\User;

/**
 * Member service.
 */
class Member extends AbstractAclService
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
     * @var User
     */
    private $userService;

    /**
     * @var \Decision\Mapper\Member
     */
    private $memberMapper;

    /**
     * @var Authorization
     */
    private $authorizationMapper;

    /**
     * @var array
     */
    private $config;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        User $userService,
        \Decision\Mapper\Member $memberMapper,
        Authorization $authorizationMapper,
        array $config
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->userService = $userService;
        $this->memberMapper = $memberMapper;
        $this->authorizationMapper = $authorizationMapper;
        $this->config = $config;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    public const MIN_SEARCH_QUERY_LENGTH = 2;

    /**
     * Returns is the member is active.
     *
     * @return bool
     */
    public function isActiveMember()
    {
        return $this->isAllowed('edit', 'organ');
    }

    public function findMemberByLidNr($lidnr)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view members.'));
        }

        return $this->memberMapper->findByLidnr($lidnr);
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

        $user = $this->userService->getIdentity();

        $sslcapath = $this->config['sslcapath'];
        $config = $this->config['dreamspark'];

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
            'sslcapath' => $sslcapath,
        ]);
        $response = $client->send();

        if (200 != $response->getStatusCode()) {
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
     * @param int $days the number of days to look ahead
     *
     * @return Collection Of members sorted by birthday
     */
    public function getBirthdayMembers($days = 0)
    {
        if (0 == $days && !$this->isAllowed('birthdays_today')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of today\'s birthdays.')
            );
        }

        if ($days > 0 && !$this->isAllowed('birthdays')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view the list of birthdays.')
            );
        }

        return $this->memberMapper->findBirthdayMembers($days);
    }

    /**
     * Get the organs a member is part of.
     *
     * @return Collection
     */
    public function getOrgans(MemberModel $member)
    {
        return $this->memberMapper->findOrgans($member);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param string $query (part of) the full name of a member
     * @pre $name must be at least MIN_SEARCH_QUERY_LENGTH
     *
     * @return Collection
     */
    public function searchMembersByName($query)
    {
        if (strlen($query) < self::MIN_SEARCH_QUERY_LENGTH) {
            throw new InvalidArgumentException(
                $this->translator->translate('Name must be at least ' . self::MIN_SEARCH_QUERY_LENGTH . ' characters')
            );
        }

        if (!$this->isAllowed('search')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to search for members.'));
        }

        return $this->memberMapper->searchByName($query);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param MemberModel $member
     * @param Meeting $meeting
     * @return bool
     * @pre $name must be at least MIN_SEARCH_QUERY_LENGTH
     *
     */
    public function canAuthorize($member, $meeting)
    {
        $maxAuthorizations = 2;

        $meetingNumber = $meeting->getNumber();
        $lidnr = $member->getLidnr();
        $authorizations = $this->authorizationMapper->findRecipientAuthorization($meetingNumber, $lidnr);

        if (count($authorizations) < $maxAuthorizations) {
            return true;
        }

        return false;
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
