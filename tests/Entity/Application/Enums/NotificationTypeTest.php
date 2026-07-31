<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application\Enums;

use App\Entity\Application\Enums\NotificationType;
use App\Security\User\Firewall;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_values;

/**
 * The enum holds everything a notification says, so a kind that is wired up wrongly is only noticed once somebody is
 * looking at a broken notification centre. These pin the parts that are easy to get wrong when a kind is added.
 */
final class NotificationTypeTest extends TestCase
{
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
     * A kind addressed to one person must never appear on the settings page, and must never be emailed as part of a
     * digest that other members receive.
     */
    public function testEveryKindAddressedToOnePersonIsEmailedOnItsOwn(): void
    {
        foreach (NotificationType::cases() as $type) {
            if ($type->isBroadcast()) {
                self::assertNull(
                    $type->emailSubject(),
                    $type->value,
                );

                continue;
            }

            self::assertNotNull(
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
        foreach (NotificationType::cases() as $type) {
            if ($type->isBroadcast()) {
                continue;
            }

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
        foreach (NotificationType::cases() as $type) {
            if ($type->isBroadcast()) {
                continue;
            }

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
            static fn (NotificationType $type): bool => $type->isBroadcast(),
        ));
    }
}
