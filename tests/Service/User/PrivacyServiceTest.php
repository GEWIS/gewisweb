<?php

declare(strict_types=1);

namespace App\Tests\Service\User;

use App\Entity\Decision\Member;
use App\Entity\User\User;
use App\Entity\User\UserSettings;
use App\Repository\User\UserSettingsRepository;
use App\Service\User\PrivacyService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

use function array_fill_keys;

/**
 * The reciprocal year-of-birth rule: the board always sees the age; otherwise a viewer only sees another member's age
 * while sharing their own, and a member who hides theirs is ageless to everyone but the board.
 */
final class PrivacyServiceTest extends TestCase
{
    public function testBoardAlwaysSeesTheYearOfBirthEvenWhenBothHideIt(): void
    {
        $service = $this->service(
            board: true,
            viewer: $this->viewer(hidesOwn: true),
            targetSettings: $this->settings(hidden: true),
        );

        self::assertTrue($this->canView($service, $this->target(42)));
    }

    public function testAnonymousViewerNeverSeesTheYearOfBirth(): void
    {
        $service = $this->service(
            board: false,
            viewer: null,
            targetSettings: null,
        );

        self::assertFalse($this->canView($service, $this->target(42)));
    }

    public function testViewerHidingTheirOwnYearOfBirthSeesNoOneElsesAge(): void
    {
        // Reciprocity: even though the target shares theirs (no settings row), the viewer forfeited the ability to see.
        $service = $this->service(
            board: false,
            viewer: $this->viewer(hidesOwn: true),
            targetSettings: null,
        );

        self::assertFalse($this->canView($service, $this->target(42)));
    }

    public function testSharingViewerCannotSeeATargetWhoHidesTheirYearOfBirth(): void
    {
        $service = $this->service(
            board: false,
            viewer: $this->viewer(hidesOwn: false),
            targetSettings: $this->settings(hidden: true),
        );

        self::assertFalse($this->canView($service, $this->target(42)));
    }

    public function testSharingViewerSeesATargetWhoSharesTheirYearOfBirth(): void
    {
        // Target has no settings row at all, which is the common "all defaults" case.
        $service = $this->service(
            board: false,
            viewer: $this->viewer(hidesOwn: false),
            targetSettings: null,
        );

        self::assertTrue($this->canView($service, $this->target(42)));
    }

    public function testVisibilityAppliesReciprocityAcrossTheWholeSet(): void
    {
        $sharer = $this->target(1);
        $hider = $this->target(2);

        $security = self::createStub(Security::class);
        $security->method('isGranted')->willReturn(false);
        $security->method('getUser')->willReturn($this->viewer(hidesOwn: false));

        $repository = self::createStub(UserSettingsRepository::class);
        $repository->method('findByLidnrs')->willReturn([2 => $this->settings(hidden: true)]);

        $visibility = new PrivacyService(
            $security,
            $repository,
        )->yearOfBirthVisibilityFor([$sharer, $hider]);

        self::assertSame(
            [
                1 => true,
                2 => false,
            ],
            $visibility,
        );
    }

    private function canView(
        PrivacyService $service,
        Member $target,
    ): bool {
        return $service->yearOfBirthVisibilityFor([$target])[$target->getLidnr()];
    }

    private function service(
        bool $board,
        ?User $viewer,
        ?UserSettings $targetSettings,
    ): PrivacyService {
        $security = self::createStub(Security::class);
        $security->method('isGranted')->willReturn($board);
        $security->method('getUser')->willReturn($viewer);

        $repository = self::createStub(UserSettingsRepository::class);
        $repository->method('findByLidnrs')->willReturnCallback(
            static fn (array $lidnrs): array => null === $targetSettings
                ? []
                : array_fill_keys(
                    $lidnrs,
                    $targetSettings,
                ),
        );

        return new PrivacyService(
            $security,
            $repository,
        );
    }

    private function viewer(bool $hidesOwn): User
    {
        $viewer = self::createStub(User::class);
        $viewer->method('hasHiddenYearOfBirth')->willReturn($hidesOwn);

        return $viewer;
    }

    private function settings(bool $hidden): UserSettings
    {
        $settings = self::createStub(UserSettings::class);
        $settings->method('getHideYearOfBirth')->willReturn($hidden);

        return $settings;
    }

    private function target(int $lidnr): Member
    {
        $member = self::createStub(Member::class);
        $member->method('getLidnr')->willReturn($lidnr);

        return $member;
    }
}
