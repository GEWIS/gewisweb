<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\NotificationType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Turns the subject ids a run of notifications carries into names a reader recognises.
 *
 * One per module rather than a branch in a central class, so a domain that grows a notification brings its own naming
 * with it instead of everybody editing the same match and the same constructor.
 */
#[AutoconfigureTag('app.notification_subject_namer')]
interface NotificationSubjectNamerInterface
{
    public function supports(NotificationType $type): bool;

    /**
     * The names of these subjects, keyed by subject id. A subject that has since been removed simply drops out.
     *
     * @param int[] $subjectIds
     *
     * @return array<int, array{en: string, nl: string}>
     */
    public function namesFor(
        NotificationType $type,
        array $subjectIds,
    ): array;
}
