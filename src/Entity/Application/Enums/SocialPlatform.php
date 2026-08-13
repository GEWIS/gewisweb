<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use function explode;
use function ltrim;
use function preg_match;
use function rawurldecode;
use function rtrim;
use function sprintf;
use function strtok;
use function strtolower;
use function trim;

/**
 * The places a body or a company can be followed. What is stored is the handle, never a URL: a pasted link carries
 * whatever the app it was copied from wanted to know about the reader, and rebuilding the address from a handle leaves
 * none of that behind.
 *
 * Two of these are not a plain username. Discord identifies a server by an invite code, and a Mastodon account only
 * means something together with the instance it lives on, so both keep their own address template and their own idea of
 * what a valid handle looks like.
 */
enum SocialPlatform: string
{
    case Instagram = 'instagram';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';
    case Twitch = 'twitch';
    case Spotify = 'spotify';
    case Discord = 'discord';
    case GitHub = 'github';
    case Mastodon = 'mastodon';

    /** A username, an invite code or an instance-qualified handle: what may appear between the slashes. */
    private const string USERNAME_PATTERN = '^[A-Za-z0-9._-]{1,64}$';

    /** `user@instance`, where the instance is a hostname. */
    private const string MASTODON_PATTERN = '^[A-Za-z0-9._-]{1,64}@[A-Za-z0-9.-]{3,253}\.[A-Za-z]{2,}$';

    /**
     * The Font Awesome brand icon this platform is recognised by.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Instagram => 'fab fa-instagram',
            self::TikTok => 'fab fa-tiktok',
            self::YouTube => 'fab fa-youtube',
            self::Twitch => 'fab fa-twitch',
            self::Spotify => 'fab fa-spotify',
            self::Discord => 'fab fa-discord',
            self::GitHub => 'fab fa-github',
            self::Mastodon => 'fab fa-mastodon',
        };
    }

    /**
     * What to write in the box, so nobody has to guess whether the at-sign belongs there.
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::Discord => 'aBcD1234',
            self::Mastodon => 'gewis@mastodon.social',
            default => 'gewis',
        };
    }

    /**
     * The regular expression a handle for this platform has to match, without delimiters.
     */
    public function handlePattern(): string
    {
        return match ($this) {
            self::Mastodon => self::MASTODON_PATTERN,
            default => self::USERNAME_PATTERN,
        };
    }

    /**
     * The address this handle points at. A Mastodon handle resolves against its own instance, which is why it has to
     * carry one.
     */
    public function urlFor(string $handle): string
    {
        return match ($this) {
            self::Instagram => sprintf(
                'https://www.instagram.com/%s',
                $handle,
            ),
            self::TikTok => sprintf(
                'https://www.tiktok.com/@%s',
                $handle,
            ),
            self::YouTube => sprintf(
                'https://www.youtube.com/@%s',
                $handle,
            ),
            self::Twitch => sprintf(
                'https://www.twitch.tv/%s',
                $handle,
            ),
            self::Spotify => sprintf(
                'https://open.spotify.com/user/%s',
                $handle,
            ),
            self::Discord => sprintf(
                'https://discord.gg/%s',
                $handle,
            ),
            self::GitHub => sprintf(
                'https://github.com/%s',
                $handle,
            ),
            self::Mastodon => $this->mastodonUrl($handle),
        };
    }

    /**
     * The handle inside whatever somebody pasted. People copy a profile link rather than type a username, and that link
     * arrives with the tracking parameters the app it was copied from added, so the address is thrown away here and
     * rebuilt from the handle by {@see self::urlFor()} when it is needed.
     *
     * Anything that is not recognisable is handed back trimmed, for the validator to refuse with a message about this
     * platform rather than about parsing.
     */
    public function normaliseHandle(string $input): string
    {
        $handle = trim($input);
        if ('' === $handle) {
            return '';
        }

        // A link is only its path: the host says which platform it is, which is already known here, and the query
        // string is the part worth losing.
        //
        // A host is only read as one when a scheme or a path says so. Plenty of usernames carry a dot, and reading
        // `gewis.official` as a website would leave nothing of it.
        if (
            1 === preg_match(
                '#^(?:[a-z][a-z0-9+.-]*://[A-Za-z0-9.-]+\.[A-Za-z]{2,}|[A-Za-z0-9.-]+\.[A-Za-z]{2,}(?=/))(/.*)?$#',
                $handle,
                $matches,
            )
        ) {
            if (self::Mastodon === $this) {
                return $this->mastodonHandleFromUrl($handle);
            }

            $handle = ltrim(
                $matches[1] ?? '',
                '/',
            );
        }

        // Everything from the first separator onwards belongs to the address, not to the handle.
        $handle = strtok(
            $handle,
            '/?#',
        );
        if (false === $handle) {
            return '';
        }

        return ltrim(
            rawurldecode($handle),
            '@',
        );
    }

    /**
     * How the handle is written out next to the icon. Everything that shows an at-sign in its own interface keeps it;
     * an invite code and a repository owner do not have one.
     */
    public function displayHandle(string $handle): string
    {
        return match ($this) {
            self::Instagram,
            self::TikTok,
            self::YouTube,
            self::Mastodon => '@' . ltrim(
                $handle,
                '@',
            ),
            default => $handle,
        };
    }

    /**
     * A Mastodon account only resolves against the instance it lives on, so its handle carries one.
     */
    private function mastodonUrl(string $handle): string
    {
        [
            $user,
            $instance,
        ] = explode(
            '@',
            $handle . '@',
            2,
        );

        return sprintf(
            'https://%s/@%s',
            strtolower(rtrim(
                $instance,
                '@',
            )),
            $user,
        );
    }

    /**
     * `https://instance/@user` written the way a Mastodon account is written down, which is the way it is stored.
     */
    private function mastodonHandleFromUrl(string $url): string
    {
        if (
            1 !== preg_match(
                '#^(?:[a-z][a-z0-9+.-]*://)?([A-Za-z0-9.-]+\.[A-Za-z]{2,})/@([A-Za-z0-9._-]{1,64})#',
                $url,
                $matches,
            )
        ) {
            return $url;
        }

        return sprintf(
            '%s@%s',
            $matches[2],
            strtolower($matches[1]),
        );
    }
}
