<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application\Enums;

use App\Entity\Application\Enums\SocialPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function preg_match;
use function sprintf;

/**
 * A social link stores the handle and nothing else, so this is where the reduction of whatever somebody pasted down to
 * that handle is pinned, together with the address it is rebuilt into. The point of the exercise is that a link copied
 * out of an app arrives carrying the parameters that app added to it, and none of that may survive.
 */
final class SocialPlatformTest extends TestCase
{
    /**
     * @return iterable<string, array{SocialPlatform, string, string}>
     */
    public static function pastedInput(): iterable
    {
        yield 'a plain username is left alone' => [
            SocialPlatform::Instagram,
            'svgewis',
            'svgewis',
        ];

        yield 'a leading at-sign is not part of the handle' => [
            SocialPlatform::TikTok,
            '@svgewis',
            'svgewis',
        ];

        yield 'surrounding whitespace is dropped' => [
            SocialPlatform::GitHub,
            '  GEWIS  ',
            'GEWIS',
        ];

        yield 'a pasted profile link becomes its handle' => [
            SocialPlatform::Instagram,
            'https://www.instagram.com/svgewis/',
            'svgewis',
        ];

        yield 'the tracking a link was copied with is thrown away' => [
            SocialPlatform::Instagram,
            'https://www.instagram.com/svgewis/?igsh=MXFqZ3Rpbg%3D%3D&utm_source=qr',
            'svgewis',
        ];

        yield 'a link without a scheme is still a link' => [
            SocialPlatform::Twitch,
            'twitch.tv/gewis',
            'gewis',
        ];

        yield 'a channel link keeps its at-sign out of the handle' => [
            SocialPlatform::YouTube,
            'https://www.youtube.com/@svgewis',
            'svgewis',
        ];

        yield 'an invite link becomes its code' => [
            SocialPlatform::Discord,
            'https://discord.gg/aBcD1234',
            'aBcD1234',
        ];

        yield 'a mastodon account keeps its instance' => [
            SocialPlatform::Mastodon,
            '@gewis@mastodon.social',
            'gewis@mastodon.social',
        ];

        yield 'a mastodon profile link is written the way an account is' => [
            SocialPlatform::Mastodon,
            'https://Mastodon.Social/@gewis',
            'gewis@mastodon.social',
        ];
    }

    #[DataProvider('pastedInput')]
    public function testWhateverWasPastedIsReducedToAHandle(
        SocialPlatform $platform,
        string $input,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            $platform->normaliseHandle($input),
        );
    }

    /**
     * Whatever survives normalisation has to be something the form will then accept, or a pasted link would be refused
     * for looking like the link it no longer is.
     */
    #[DataProvider('pastedInput')]
    public function testTheHandleThatComesOutIsOneThePlatformAccepts(
        SocialPlatform $platform,
        string $input,
        string $expected,
    ): void {
        self::assertNotSame(
            '',
            $expected,
        );

        self::assertSame(
            1,
            preg_match(
                sprintf(
                    '/%s/',
                    $platform->handlePattern(),
                ),
                $platform->normaliseHandle($input),
            ),
        );
    }

    /**
     * @return iterable<string, array{SocialPlatform, string, string}>
     */
    public static function handles(): iterable
    {
        yield 'instagram' => [
            SocialPlatform::Instagram,
            'svgewis',
            'https://www.instagram.com/svgewis',
        ];

        yield 'tiktok' => [
            SocialPlatform::TikTok,
            'svgewis',
            'https://www.tiktok.com/@svgewis',
        ];

        yield 'youtube' => [
            SocialPlatform::YouTube,
            'svgewis',
            'https://www.youtube.com/@svgewis',
        ];

        yield 'twitch' => [
            SocialPlatform::Twitch,
            'gewis',
            'https://www.twitch.tv/gewis',
        ];

        yield 'spotify' => [
            SocialPlatform::Spotify,
            'gewis',
            'https://open.spotify.com/user/gewis',
        ];

        yield 'discord' => [
            SocialPlatform::Discord,
            'aBcD1234',
            'https://discord.gg/aBcD1234',
        ];

        yield 'github' => [
            SocialPlatform::GitHub,
            'GEWIS',
            'https://github.com/GEWIS',
        ];

        yield 'mastodon resolves against its own instance' => [
            SocialPlatform::Mastodon,
            'gewis@mastodon.social',
            'https://mastodon.social/@gewis',
        ];
    }

    #[DataProvider('handles')]
    public function testTheAddressIsRebuiltFromTheHandle(
        SocialPlatform $platform,
        string $handle,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            $platform->urlFor($handle),
        );
    }

    /**
     * An empty box is how "not on this platform" is written down, so it has to survive normalisation as empty rather
     * than as something the pattern would then refuse.
     */
    public function testAnEmptyBoxStaysEmpty(): void
    {
        foreach (SocialPlatform::cases() as $platform) {
            self::assertSame(
                '',
                $platform->normaliseHandle('   '),
            );
        }
    }

    public function testEveryPlatformNamesAnIconAndAPlaceholder(): void
    {
        foreach (SocialPlatform::cases() as $platform) {
            self::assertStringStartsWith(
                'fab fa-',
                $platform->icon(),
            );
            self::assertNotSame(
                '',
                $platform->placeholder(),
            );
        }
    }
}
