<?php

declare(strict_types=1);

namespace Decision\Service;

use Decision\Mapper\Authorization as AuthorizationMapper;
use Decision\Mapper\Member as MemberMapper;
use Decision\Model\Meeting as MeetingModel;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

use function count;
use function strlen;

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

    public const int MIN_SEARCH_QUERY_LENGTH = 2;

    /**
     * Returns is the member is active.
     */
    public function isActiveMember(): bool
    {
        return $this->aclService->isAllowed('edit', 'organ');
    }

    public function findMemberByLidNr(int $lidnr): ?MemberModel
    {
        if (
            !$this->aclService->isAllowed('view', 'member')
            && (
                !$this->aclService->isAllowed('view_self', 'member')
                || $lidnr !== $this->aclService->getUserIdentityOrThrowException()->getLidnr()
            )
        ) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view members'));
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
     * @return MemberModel[] sorted by birthday
     */
    public function getBirthdayMembers(int $days = 0): array
    {
        return $this->memberMapper->findBirthdayMembers($days);
    }

    /**
     * Get the organs a member is part of.
     *
     * @return OrganModel[]
     */
    public function getOrgans(MemberModel $member): array
    {
        return $this->memberMapper->findOrgans($member);
    }

    /**
     * Find a member by (part of) its name.
     *
     * @param string $query (part of) the full name of a member
     *
     * @return array<array-key, array{
     *     lidnr: int,
     *     fullName: string,
     *     generation: int,
     * }>
     *
     * @pre $name must be at least MIN_SEARCH_QUERY_LENGTH
     */
    public function searchMembersByName(string $query): array
    {
        if (strlen($query) < self::MIN_SEARCH_QUERY_LENGTH) {
            return [];
        }

        return $this->memberMapper->searchByName($query);
    }

    /**
     * Determine if a member can be authorized for a meeting.
     */
    public function canAuthorize(
        MemberModel $member,
        MeetingModel $meeting,
    ): bool {
        $maxAuthorizations = 2;
        $authorizations = $this->authorizationMapper->findRecipientAuthorization($meeting->getNumber(), $member);

        return count($authorizations) < $maxAuthorizations;
    }
}
