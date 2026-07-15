<?php

declare(strict_types=1);

namespace App\Service\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Reports whether a domain still references a given stored file. Because stored files are content-addressed,
 * two entities (even across domains) can legitimately share one physical file, so {@see FileStorage::remove()} must
 * never delete the bytes while another row still references them. Each domain that persists stored paths
 * (photos, album covers, company logos, organ images) implements this and reports on its own tables; the storage
 * service consults every implementation before unlinking.
 *
 * Implementations must be worker-safe and side-effect free, and must reflect the committed state. Callers remove
 * their own entity and flush before asking, so a provider that returns `false` means no one else needs it.
 */
#[AutoconfigureTag('app.file_reference_provider')]
interface FileReferenceProviderInterface
{
    /**
     * Whether any entity this provider knows about still references the given stored path.
     */
    public function references(string $path): bool;
}
