<?php

declare(strict_types=1);

namespace App\Entity\Frontpage\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The ways of responding to a poll comment without writing one back.
 */
enum PollCommentReactionType: string implements TranslatableInterface
{
    case Like = 'like';
    case Love = 'love';
    case Insightful = 'insightful';
    case Funny = 'funny';

    /**
     * The Font Awesome icon the reaction is drawn with.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Like => 'fa-thumbs-up',
            self::Love => 'fa-heart',
            self::Insightful => 'fa-lightbulb',
            self::Funny => 'fa-face-grin-tears',
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Like => $translator->trans(
                'Like',
                locale: $locale,
            ),
            self::Love => $translator->trans(
                'Love',
                locale: $locale,
            ),
            self::Insightful => $translator->trans(
                'Insightful',
                locale: $locale,
            ),
            self::Funny => $translator->trans(
                'Funny',
                locale: $locale,
            ),
        };
    }
}
