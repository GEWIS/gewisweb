<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\User\UserSettingsRepository;
use Symfony\Bundle\SecurityBundle\Security;

use function array_fill_keys;
use function array_map;

/**
 * The single source of truth for member-data visibility rules. Currently it decides who may see a member's year of
 * birth (and thus age); it is the hook future surfaces (date of birth / address / email in member search) will reuse.
 *
 * Year-of-birth rule: the board and admins always see it. Otherwise it is reciprocal - to see anyone's year
 * of birth you must be sharing your own, and a member who hides theirs is shown ageless to everyone but the board.
 */
final readonly class PrivacyService
{
    public function __construct(
        private Security $security,
        private UserSettingsRepository $settingsRepository,
    ) {
    }

    /**
     * Whether the current viewer may see each target member's year of birth, keyed by `lidnr`. Resolves the viewer's
     * privilege and own setting once, and, when relevant, loads every target's setting in a single query.
     *
     * @param Member[] $targets
     *
     * @return array<int, bool>
     */
    public function yearOfBirthVisibilityFor(array $targets): array
    {
        $lidnrs = array_map(
            static fn (Member $member): int => $member->getLidnr(),
            $targets,
        );

        if ($this->security->isGranted(UserRoles::Board->value)) {
            return array_fill_keys(
                $lidnrs,
                true,
            );
        }

        $viewer = $this->security->getUser();
        if (
            !$viewer instanceof User
            || $viewer->hasHiddenYearOfBirth()
        ) {
            return array_fill_keys(
                $lidnrs,
                false,
            );
        }

        $settings = $this->settingsRepository->findByLidnrs($lidnrs);

        $visibility = [];
        foreach ($targets as $target) {
            $lidnr = $target->getLidnr();
            $visibility[$lidnr] = !(($settings[$lidnr] ?? null)?->getHideYearOfBirth() ?? false);
        }

        return $visibility;
    }
}
