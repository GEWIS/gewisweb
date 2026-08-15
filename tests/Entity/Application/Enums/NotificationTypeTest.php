<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application\Enums;

use App\Entity\Application\Enums\NotificationAddressing;
use App\Entity\Application\Enums\NotificationType;
use App\Security\User\Firewall;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_values;
use function in_array;

/**
 * The enum holds everything a notification says, so a kind that is wired up wrongly is only noticed once somebody is
 * looking at a broken notification centre. These pin the parts that are easy to get wrong when a kind is added.
 */
final class NotificationTypeTest extends TestCase
{
    /**
     * The kinds that report something happening to an account. Listed rather than derived, so adding a kind is a
     * decision about which of these rules it belongs to instead of quietly inheriting them.
     */
    private const array SECURITY = [
        NotificationType::SignIn,
        NotificationType::PasswordChanged,
        NotificationType::MfaEnabled,
        NotificationType::MfaDisabled,
        NotificationType::BackupCodesRegenerated,
    ];

    /**
     * Only what a member can be told about themselves belongs in the always-on list on the settings page; what goes to
     * a role is somebody's work queue, not their preferences.
     */
    public function testEachKindIsAddressedExactlyOneWay(): void
    {
        self::assertSame(
            [
                NotificationType::AlbumPublished,
                NotificationType::ActivityPublished,
            ],
            $this->addressedTo(NotificationAddressing::Everyone),
        );
        self::assertSame(
            [
                NotificationType::ActivityAwaitingReview,
                NotificationType::CompanyRevisionAwaitingReview,
                NotificationType::VacancyRevisionAwaitingReview,
                NotificationType::CompanyBannerAwaitingReview,
                NotificationType::OrganInformationRevisionAwaitingReview,
                NotificationType::PollRevisionAwaitingReview,
            ],
            $this->addressedTo(NotificationAddressing::Role),
        );
        self::assertNotContains(
            NotificationType::ActivityAwaitingReview,
            $this->addressedTo(NotificationAddressing::Account),
        );
    }

    /**
     * @return list<NotificationType>
     */
    private function addressedTo(NotificationAddressing $addressing): array
    {
        return array_values(array_filter(
            NotificationType::cases(),
            static fn (NotificationType $type): bool => $addressing === $type->addressing(),
        ));
    }

    public function testOnlyTheKindsMeantForEveryoneAreOfferedAsEmailTopics(): void
    {
        self::assertSame(
            [
                NotificationType::AlbumPublished,
                NotificationType::ActivityPublished,
            ],
            $this->broadcast(),
        );
    }

    /**
     * A security notice is mailed from its own handler, so it needs a subject line. Anything that goes out in a digest
     * takes its subject from whatever else is in that digest, and must not carry one.
     */
    public function testOnlySecurityNoticesAreEmailedOnTheirOwn(): void
    {
        foreach (NotificationType::cases() as $type) {
            if (
                in_array(
                    $type,
                    self::SECURITY,
                    true,
                )
            ) {
                self::assertNotNull(
                    $type->emailSubject(),
                    $type->value,
                );

                continue;
            }

            self::assertNull(
                $type->emailSubject(),
                $type->value,
            );
        }
    }

    /**
     * Whichever firewall the reader is on, a notice about their own account has to point at their own security page.
     */
    public function testAccountNoticesFollowTheRecipientToTheirOwnSecurityPage(): void
    {
        foreach (self::SECURITY as $type) {
            self::assertSame(
                'user_security_index',
                $type->route(Firewall::Main),
                $type->value,
            );
            self::assertSame(
                'company_user_security_index',
                $type->route(Firewall::Company),
                $type->value,
            );
            self::assertSame(
                [],
                $type->routeParameters(null),
                $type->value,
            );
        }
    }

    /**
     * These carry a frozen label rather than a subject, so the sentence has to have somewhere to put it.
     */
    public function testAccountNoticesSayWhereTheyCameFrom(): void
    {
        foreach (self::SECURITY as $type) {
            self::assertStringContainsString(
                '%name%',
                $type->message('Chrome 124 · Windows 11')->getMessage(),
                $type->value,
            );
        }
    }

    /**
     * @return list<NotificationType>
     */
    private function broadcast(): array
    {
        return array_values(array_filter(
            NotificationType::cases(),
            static fn (NotificationType $type): bool => NotificationAddressing::Everyone === $type->addressing(),
        ));
    }
}
