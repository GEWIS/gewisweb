<?php

declare(strict_types=1);

namespace App\Service\Application;

use RuntimeException;

/**
 * Thrown by {@see FileStorage} when a file cannot be accepted or stored: an unreadable source, a MIME type outside the
 * namespace whitelist, or a file exceeding the namespace size limit. Callers (e.g. the photo upload endpoint) catch it
 * to report a per-file failure without aborting a whole batch.
 */
final class FileStorageException extends RuntimeException
{
}
