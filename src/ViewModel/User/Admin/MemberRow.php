<?php

declare(strict_types=1);

namespace App\ViewModel\User\Admin;

use App\Entity\Decision\Enums\MembershipTypes;
use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use DateTimeImmutable;

/**
 * Read-model view of a {@see Member} row for the admin users overview. Pre-computes everything the template needs to
 * render badges and status columns, so the Twig stays declarative.
 *
 * @psalm-type RoleBadgeArray = array{label: string, expiresAt: ?DateTimeImmutable}
 */
final readonly class MemberRow
{
    /**
     * @param list<array{label: string, expiresAt: ?DateTimeImmutable}> $roleBadges
     */
    public function __construct(
        public int $lidnr,
        public string $fullName,
        public MembershipTypes $type,
        public ?DateTimeImmutable $membershipEndsOn,
        public bool $isActivated,
        public bool $hidden,
        public bool $deleted,
        public bool $expired,
        public bool $mfaEnabled,
        public array $roleBadges,
    ) {
    }

    public static function fromMember(
        Member $member,
        ?User $user,
    ): self {
        $roleBadges = [];
        if ($member->isActive()) {
            $roleBadges[] = [
                'label' => UserRoles::ActiveMember->value,
                'expiresAt' => null,
            ];
        }

        if ($member->isBoardMember()) {
            $roleBadges[] = [
                'label' => UserRoles::Board->value,
                'expiresAt' => null,
            ];
        }

        if (null !== $user) {
            foreach ($user->getRoleEntities() as $userRole) {
                if (!$userRole->isActive()) {
                    continue;
                }

                $expiration = $userRole->getExpiration();
                $roleBadges[] = [
                    'label' => $userRole->getRole()->value,
                    'expiresAt' => null === $expiration
                        ? null
                        : DateTimeImmutable::createFromMutable($expiration),
                ];
            }
        }

        $membershipEndsOn = $member->getMembershipEndsOn();

        return new self(
            lidnr: $member->getLidnr(),
            fullName: $member->getFullName(),
            type: $member->getType(),
            membershipEndsOn: null === $membershipEndsOn
                ? null
                : DateTimeImmutable::createFromMutable($membershipEndsOn),
            isActivated: null !== $user,
            hidden: $member->getHidden(),
            deleted: $member->getDeleted(),
            expired: $member->isExpired(),
            mfaEnabled: null !== $user && $user->isTotpAuthenticationEnabled(),
            roleBadges: $roleBadges,
        );
    }
}
