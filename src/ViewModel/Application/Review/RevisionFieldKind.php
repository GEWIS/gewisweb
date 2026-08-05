<?php

declare(strict_types=1);

namespace App\ViewModel\Application\Review;

/**
 * How a field is rendered, and with it what its old and new values hold. The set is closed on purpose: a domain that
 * wants something else describes it with what is here or the kind is added once, for everybody.
 */
enum RevisionFieldKind: string
{
    /** Short text, shown as a character diff. */
    case Text = 'text';

    /** A body of text, shown as a line diff so a long description stays readable. */
    case LongText = 'long-text';

    /** The name of something this revision points at, with what it pointed at before. */
    case Reference = 'reference';

    /** A value out of a fixed set, shown as a coloured badge. */
    case Badge = 'badge';

    /** A set of labels, with what this revision added and dropped. */
    case Tags = 'tags';

    /** A boolean, coloured by whether this revision turned it on or off. */
    case Flag = 'flag';

    /** A start and an end, either of which may be open. */
    case DateRange = 'date-range';

    /** A stored image path, shown beside the one it replaces. */
    case Image = 'image';
}
