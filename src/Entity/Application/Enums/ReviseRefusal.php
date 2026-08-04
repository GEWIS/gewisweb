<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

/**
 * Why a revisable cannot be revised right now. The reasons are the same in every module; only the wording and where
 * the reader lands afterwards belong to the domain, so those stay with the controller.
 *
 * @see \App\Service\Application\RevisionReviser
 */
enum ReviseRefusal
{
    /** There is already a draft to work on, so the reader wants the edit screen rather than a second one. */
    case AlreadyADraft;

    /** It is with the reviewers, and a new draft now would move the head away from what they are deciding on. */
    case UnderReview;

    /** The chain was closed for good, which only the reviewers can undo. */
    case Closed;
}
