<?php

declare(strict_types=1);

namespace App\Tests\Entity\User;

use App\Entity\User\User;
use App\Entity\User\UserSettings;
use PHPUnit\Framework\TestCase;

/**
 * The GDPR export must expose every stored preference, so a new setting cannot be added without also surfacing it to
 * the member. This pins the exported shape and values.
 */
final class UserSettingsTest extends TestCase
{
    public function testGdprArrayExportsEverySetting(): void
    {
        $settings = new UserSettings(self::createStub(User::class));
        $settings->setPhotoTaggingOptOut(true);
        $settings->setHideYearOfBirth(true);

        self::assertSame(
            [
                'disableCosmetics' => false,
                'photoTaggingOptOut' => true,
                'hideYearOfBirth' => true,
                'hideBirthdayOnFrontpage' => false,
            ],
            $settings->toGdprArray(),
        );
    }
}
