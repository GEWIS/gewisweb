<?php

namespace Decision\Service;

use DateTime;
use Decision\Mapper\{
    Member as MemberMapper,
};
use Decision\Model\Member as MemberModel;
use Exception;
use Laminas\Mvc\I18n\Translator;
use Photo\Service\Photo as PhotoService;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Permissions\NotAllowedException;

/**
 * Member service.
 */
class MemberInfo
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly PhotoService $photoService,
        private readonly MemberMapper $memberMapper,
        private readonly ApiAppAuthenticationMapper $apiAppAuthenticationMapper,
        private readonly array $photoConfig,
    ) {
    }

    /**
     * Obtain information about the current user.
     *
     * @param int|null $lidnr
     *
     * @return array|null
     *
     * @throws Exception
     */
    public function getMembershipInfo(?int $lidnr = null): ?array
    {
        if (null === $lidnr && !$this->aclService->isAllowed('view_self', 'member')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view membership info.'));
        } elseif (null !== $lidnr && !$this->aclService->isAllowed('view', 'member')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view members.'));
        }

        if (null === $lidnr) {
            $lidnr = $this->aclService->getUserIdentityOrThrowException()->getLidnr();
        }

        $member = $this->memberMapper->findByLidnr($lidnr);

        if (null === $member) {
            return null;
        }

        if (
            $member->isExpired()
            && !$this->aclService->isAllowed('view_expired', 'member')
        ) {
            return null;
        }

        $memberships = $this->getOrganMemberships($member);

        // Base directory for retrieving photos
        $basedir = $this->photoService->getBaseDirectory();

        $profilePhoto = $this->photoService->getProfilePhoto($lidnr);
        $isExplicitProfilePhoto = $this->photoService->hasExplicitProfilePhoto($lidnr);

        return [
            'member' => $member,
            'memberships' => $memberships,
            'profilePhoto' => $profilePhoto,
            'isExplicitProfilePhoto' => $isExplicitProfilePhoto,
            'basedir' => $basedir,
            'photoConfig' => $this->photoConfig,
            'apps' => $this->apiAppAuthenticationMapper->getFirstAndLastAuthenticationPerApiApp($member),
        ];
    }

    /**
     * Gets a list of all organs which the member currently is part of.
     *
     * @param MemberModel $member
     *
     * @return array
     */
    public function getOrganMemberships(MemberModel $member): array
    {
        $memberships = [
            'current' => [],
            'historical' => [],
        ];

        foreach ($this->memberMapper->findCurrentInstallations($member) as $install) {
            if (!isset($memberships['current'][$install->getOrgan()->getAbbr()])) {
                $memberships['current'][$install->getOrgan()->getAbbr()] = [
                    'organ' => $install->getOrgan(),
                    'functions' => [],
                ];
            }

            if (
                'Lid' !== $install->getFunction()
                && 'Inactief Lid' !== $install->getFunction()
            ) {
                $function = $this->translator->translate($install->getFunction());
                $memberships['current'][$install->getOrgan()->getAbbr()]['functions'][] = $function;
            }
        }

        foreach ($this->memberMapper->findHistoricalInstallations($member) as $install) {
            if (!isset($memberships['historical'][$install->getOrgan()->getAbbr()])) {
                $memberships['historical'][$install->getOrgan()->getAbbr()] = [
                    'organ' => $install->getOrgan(),
                    'functions' => [],
                ];
            }

            if (
                'Lid' !== $install->getFunction()
                && 'Inactief Lid' !== $install->getFunction()
            ) {
                $function = $this->translator->translate($install->getFunction());
                $memberships['historical'][$install->getOrgan()->getAbbr()]['functions'][] = $function;
            }
        }


        return $memberships;
    }
}
