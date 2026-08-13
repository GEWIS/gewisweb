<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Enums\SocialPlatform;
use App\Entity\Application\Traits\IdentifiableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;

/**
 * One place a body or a company can be followed, as the platform and the handle it is known by there. The address is
 * never stored: {@see SocialPlatform::urlFor()} builds it when a page needs one, so nothing a reader was tracked with
 * survives being pasted in.
 *
 * A social link belongs to a revision rather than to the aggregate, so adding or dropping one goes through review like
 * everything else the page says. Each domain's concrete subclass is the entity, and declares the association back to
 * the revision that owns it; this class holds only what every one of them says, as {@see LocalisedText} does.
 */
abstract class AbstractSocialLink
{
    use IdentifiableTrait;

    #[Column(type: Types::STRING)]
    protected string $handle = '';

    /**
     * Final so every concrete subclass shares this exact signature, which lets {@see self::copy()} use `new static()`.
     * The platform is settled when the link is made and never changes: a handle only means anything on the platform it
     * was written for, and it is normalised against that platform on the way in.
     */
    final public function __construct(
        #[Column(
            type: Types::STRING,
            enumType: SocialPlatform::class,
        )]
        protected SocialPlatform $platform,
    ) {
    }

    public function getPlatform(): SocialPlatform
    {
        return $this->platform;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * Whatever arrives is reduced to a handle first, because people paste a profile link rather than type a username.
     * Doing it here rather than in a form means a fixture and an import cannot store a URL either.
     */
    public function setHandle(string $handle): void
    {
        $this->handle = $this->platform->normaliseHandle($handle);
    }

    /**
     * Where following this link leads.
     */
    public function getUrl(): string
    {
        return $this->platform->urlFor($this->handle);
    }

    /**
     * How the handle reads next to its icon.
     */
    public function getDisplayHandle(): string
    {
        return $this->platform->displayHandle($this->handle);
    }

    /**
     * A fresh, unpersisted copy for the cloners, so orphan removal can never take the source revision's row with it.
     */
    public function copy(): static
    {
        $copy = new static($this->platform);
        $copy->setHandle($this->handle);

        return $copy;
    }
}
