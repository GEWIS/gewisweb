<?php

declare(strict_types=1);

namespace App\Entity\Application\Traits;

use App\Entity\Application\AbstractSocialLink;
use App\Entity\Application\Enums\SocialPlatform;
use Doctrine\Common\Collections\Collection;

/**
 * A revision's social links, read and written as a handle per platform. A form asks for one box per platform and gets
 * back a map, which is the shape this turns into rows: a handle that arrived becomes or updates a link, and a platform
 * that came back empty loses the link it had.
 *
 * The collection itself, with its mapping and its own concrete link class, stays on the revision; so does making one,
 * which is where the foreign key back to the revision is set.
 */
trait HasSocialLinksTrait
{
    /**
     * A fresh link for this platform, already pointing back at this revision.
     */
    abstract protected function newSocialLink(SocialPlatform $platform): AbstractSocialLink;

    /**
     * @return Collection<array-key, covariant AbstractSocialLink>
     */
    abstract public function getSocialLinks(): Collection;

    /**
     * The handle per platform, for the platforms this revision is on.
     *
     * @return array<string, string>
     */
    public function getSocialHandles(): array
    {
        $handles = [];
        foreach ($this->getSocialLinks() as $link) {
            $handles[$link->getPlatform()->value] = $link->getHandle();
        }

        return $handles;
    }

    /**
     * @param array<string, string|null> $handles keyed by {@see SocialPlatform} value
     */
    public function updateSocialLinks(array $handles): void
    {
        $links = $this->getSocialLinks();

        $existing = [];
        foreach ($links as $link) {
            $existing[$link->getPlatform()->value] = $link;
        }

        foreach (SocialPlatform::cases() as $platform) {
            $handle = $handles[$platform->value] ?? null;
            $link = $existing[$platform->value] ?? null;

            if (
                null === $handle
                || '' === $handle
            ) {
                if (null !== $link) {
                    $links->removeElement($link);
                }

                continue;
            }

            $link ??= $this->newSocialLink($platform);
            $link->setHandle($handle);

            if ($links->contains($link)) {
                continue;
            }

            $links->add($link);
        }
    }
}
