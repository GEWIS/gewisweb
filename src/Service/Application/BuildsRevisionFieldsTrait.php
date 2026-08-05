<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\LocalisedText;
use App\ViewModel\Application\Review\RevisionAudience;
use App\ViewModel\Application\Review\RevisionDateRange;
use App\ViewModel\Application\Review\RevisionField;
use App\ViewModel\Application\Review\RevisionFieldKind;
use App\ViewModel\Application\Review\RevisionFieldValue;
use App\ViewModel\Application\Review\RevisionFlag;
use App\ViewModel\Application\Review\RevisionTag;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * The two shapes every describer needs: a field written once per language, and one written once. Kept together so a
 * describer reads as the list of fields it is.
 */
trait BuildsRevisionFieldsTrait
{
    /**
     * A field the author fills in per language, laid out in the language columns.
     *
     * @param array<string, string> $options
     */
    protected function localisedField(
        TranslatableInterface $label,
        ?LocalisedText $old,
        LocalisedText $new,
        bool $comparable,
        RevisionFieldKind $kind = RevisionFieldKind::Text,
        array $options = [],
        RevisionAudience $audience = RevisionAudience::Everyone,
    ): RevisionField {
        $values = [];

        foreach ([Languages::Dutch, Languages::English] as $language) {
            $values[] = new RevisionFieldValue(
                $old?->getExactText($language),
                $new->getExactText($language),
                $language,
            );
        }

        return new RevisionField(
            $label,
            $kind,
            $values,
            $comparable,
            $options,
            $audience,
        );
    }

    /**
     * A field the revision holds once, laid out in a row of its own.
     *
     * @param string|bool|TranslatableInterface|RevisionDateRange|list<RevisionTag>|list<RevisionFlag>|null $old
     * @param string|bool|TranslatableInterface|RevisionDateRange|list<RevisionTag>|list<RevisionFlag>|null $new
     * @param array<string, string>                                                                         $options
     */
    protected function field(
        TranslatableInterface $label,
        RevisionFieldKind $kind,
        string|bool|TranslatableInterface|RevisionDateRange|array|null $old,
        string|bool|TranslatableInterface|RevisionDateRange|array|null $new,
        bool $comparable,
        array $options = [],
        RevisionAudience $audience = RevisionAudience::Everyone,
        ?TranslatableInterface $emptyLabel = null,
    ): RevisionField {
        return new RevisionField(
            $label,
            $kind,
            [
                new RevisionFieldValue(
                    $old,
                    $new,
                ),
            ],
            $comparable,
            $options,
            $audience,
            $emptyLabel,
        );
    }
}
