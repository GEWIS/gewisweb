<?php

declare(strict_types=1);

namespace Decision\Service;

use Decision\Mapper\Member as MemberMapper;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Exception;
use Laminas\Mvc\I18n\Translator;
use Photo\Model\Photo as PhotoModel;
use Photo\Service\Photo as PhotoService;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Permissions\NotAllowedException;

/**
 * Member service.
 *
 * @psalm-import-type ApiAppsArrayType from ApiAppAuthenticationMapper as ImportedApiAppsArrayType
 * @psalm-type OrganMembershipsArrayType = array{
 *     current: array<string, array{
 *         organ: OrganModel,
 *         functions: string[],
 *     }>,
 *     historical: array<string, array{
 *         organ: OrganModel,
 *         functions: string[],
 *     }>,
 * }
 */
class MemberInfo
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
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
     * @return ?array{
     *     member: MemberModel,
     *     memberships: OrganMembershipsArrayType,
     *     profilePhoto: ?PhotoModel,
     *     isExplicitProfilePhoto: bool,
     *     basedir: string,
     *     photoConfig: mixed[],
     *     apps: ImportedApiAppsArrayType,
     * }
     *
     * @throws Exception
     */
    public function getMembershipInfo(?int $lidnr = null): ?array
    {
        if (null === $lidnr && !$this->aclService->isAllowed('view_self', 'member')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view membership info'));
        }

        if (null !== $lidnr && !$this->aclService->isAllowed('view', 'member')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view members'));
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
     * @return OrganMembershipsArrayType
     */
    public function getOrganMemberships(MemberModel $member): array
    {
        $memberships = [
            'current' => [],
            'historical' => [],
        ];

        foreach ($this->memberMapper->findCurrentInstallations($member) as $install) {
            $foundationHash = $install->getOrgan()->getFoundation()->getHash();

            if (!isset($memberships['current'][$foundationHash])) {
                $memberships['current'][$foundationHash] = [
                    'organ' => $install->getOrgan(),
                    'functions' => [],
                ];
            }

            if (
                'Lid' === $install->getFunction()
                || 'Inactief Lid' === $install->getFunction()
            ) {
                continue;
            }

            $function = $this->translator->translate($install->getFunction());
            $memberships['current'][$foundationHash]['functions'][] = $function;
        }

        foreach ($this->memberMapper->findHistoricalInstallations($member) as $install) {
            $foundationHash = $install->getOrgan()->getFoundation()->getHash();

            if (!isset($memberships['historical'][$foundationHash])) {
                $memberships['historical'][$foundationHash] = [
                    'organ' => $install->getOrgan(),
                    'functions' => [],
                ];
            }

            if (
                'Lid' === $install->getFunction()
                || 'Inactief Lid' === $install->getFunction()
            ) {
                continue;
            }

            $function = $this->translator->translate($install->getFunction());
            $memberships['historical'][$foundationHash]['functions'][] = $function;
        }

        return $memberships;
    }
}
