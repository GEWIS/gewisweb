<?php

declare(strict_types=1);

namespace App\Security\Application;

use App\Entity\Application\Enums\StorageNamespace;

/**
 * Decides whether the current request may be served a file from a given {@see StorageNamespace}. The
 * {@see \App\Controller\Application\ImageController} consults, in priority order, the first checker whose
 * {@see supports()} matches the namespace; a namespace-specific checker (e.g. the photos one, which short-circuits for
 * full members and runs the per-photo album voter for graduates) overrides the low-priority
 * {@see DefaultServingAccessChecker}.
 *
 * Implementations MUST register themselves with `#[AutoconfigureTag('app.serving_access_checker', ['priority' => N])]`
 * (higher priority wins); the default fallback uses a very low priority.
 */
interface ServingAccessCheckerInterface
{
    /**
     * Whether this checker governs the given namespace.
     */
    public function supports(StorageNamespace $namespace): bool;

    /**
     * Whether the current request may be served the file at $path (within $namespace).
     */
    public function isGranted(
        string $path,
        StorageNamespace $namespace,
    ): bool;
}
