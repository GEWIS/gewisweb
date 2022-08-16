<?php

namespace Decision\Service;

use Decision\Mapper\{
    Authorization as AuthorizationMapper,
    Member as MemberMapper,
};
use Decision\Model\{
    Meeting as MeetingModel,
    Member as MemberModel,
};
use Laminas\Code\Exception\InvalidArgumentException;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

/**
 * Member service.
 */
class Member
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly MemberMapper $memberMapper,
        private readonly AuthorizationMapper $authorizationMapper,
    ) {
    }

    public const MIN_SEARCH_QUERY_LENGTH = 2;

    /**
     * Returns is the member is active.
     *
     * @return bool
     */
    public function isActiveMember(): bool
    {
        return $this->aclService->isAllowed('edit', 'organ');
    }

    /**
     * @param int $lidnr
     *
     * @return MemberModel|null
     */
    public function findMemberByLidNr(int $lidnr): ?MemberModel
    {
        if (!$this->aclService->isAllowed('view', 'member')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view members.'));
        }

        return $this->memberMapper->findByLidnr($lidnr);
    }

    /**
     * Get the members of which their birthday falls in the next $days days.
     *
     * When $days equals 0 or isn't given, it will give all birthdays of today.
     *
     * @param int $days the number of days to look ahead
     *
     * @return array Of members sorted by birthday
     */
    public function getBirthdayMembers(int $days = 0): array
    {
        return $this->memberMapper->findBirthdayMembers($days);
    }

    /**
     * Get the organs a member is part of.
     *
     * @param MemberModel $member
     *
     * @return array
     */
    public function getOrgans(MemberModel $member): array
    {
        return $this->memberMapper->findOrgans($member);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param string $query (part of) the full name of a member
     * @pre $name must be at least MIN_SEARCH_QUERY_LENGTH
     *
     * @return array
     */
    public function searchMembersByName(string $query): array
    {
        if (strlen($query) < self::MIN_SEARCH_QUERY_LENGTH) {
            throw new InvalidArgumentException(
                $this->translator->translate('Name must be at least ' . self::MIN_SEARCH_QUERY_LENGTH . ' characters')
            );
        }

        return $this->memberMapper->searchByName($query);
    }

    /**
     * Determine if a member can be authorized for a meeting.
     *
     * @param MemberModel $member
     * @param MeetingModel $meeting
     *
     * @return bool
     */
    public function canAuthorize(
        MemberModel $member,
        MeetingModel $meeting,
    ): bool {
        $maxAuthorizations = 2;
        $authorizations = $this->authorizationMapper->findRecipientAuthorization($meeting->getNumber(), $member);

        if (count($authorizations) < $maxAuthorizations) {
            return true;
        }

        return false;
    }
}
