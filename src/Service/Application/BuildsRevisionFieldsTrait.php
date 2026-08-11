<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\AbstractSocialLink;
use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\SocialPlatform;
use App\Entity\Application\LocalisedText;
use App\ViewModel\Application\Review\RevisionAudience;
use App\ViewModel\Application\Review\RevisionDateRange;
use App\ViewModel\Application\Review\RevisionField;
use App\ViewModel\Application\Review\RevisionFieldKind;
use App\ViewModel\Application\Review\RevisionFieldValue;
use App\ViewModel\Application\Review\RevisionFlag;
use App\ViewModel\Application\Review\RevisionTag;
use Symfony\Component\Translation\TranslatableMessage;
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
     * The social links of a revision, as one field per platform either side mentions. A platform neither side is on is
     * left out, so the section says what changed rather than listing everything on offer; when that leaves nothing, the
     * section itself drops out of the comparison.
     *
     * @param iterable<AbstractSocialLink>|null $old
     * @param iterable<AbstractSocialLink>      $new
     *
     * @return list<RevisionField>
     */
    protected function socialFields(
        ?iterable $old,
        iterable $new,
        bool $comparable,
    ): array {
        $before = $this->handlesByPlatform($old ?? []);
        $after = $this->handlesByPlatform($new);

        $fields = [];
        foreach (SocialPlatform::cases() as $platform) {
            $wasSet = isset($before[$platform->value]);
            $isSet = isset($after[$platform->value]);

            if (
                !$wasSet
                && !$isSet
            ) {
                continue;
            }

            $fields[] = $this->field(
                new TranslatableMessage($platform->name),
                RevisionFieldKind::Text,
                $before[$platform->value] ?? null,
                $after[$platform->value] ?? null,
                $comparable,
                ['width' => 'third'],
            );
        }

        return $fields;
    }

    /**
     * @param iterable<AbstractSocialLink> $links
     *
     * @return array<string, string>
     */
    private function handlesByPlatform(iterable $links): array
    {
        $handles = [];
        foreach ($links as $link) {
            $handles[$link->getPlatform()->value] = $link->getDisplayHandle();
        }

        return $handles;
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
