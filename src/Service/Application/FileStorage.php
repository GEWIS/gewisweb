<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\StorageNamespace;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Mime\MimeTypes;

use function fclose;
use function filesize;
use function fopen;
use function hash_file;
use function is_file;
use function is_readable;
use function sprintf;

/**
 * The service through which the application reads and writes stored files. It wraps a flysystem
 * {@see FilesystemOperator} rooted at `data/` (an in-memory adapter under test), so nothing else needs to know whether
 * the bytes live on local disk, S3, or memory.
 *
 * New uploads are content-addressed: {@see store()} hashes the content (sha256), derives the path from the
 * {@see StorageNamespace} plus that hash, and skips the write when identical content already exists (de-duplication).
 * Legacy files migrated from the Laminas layout keep their existing (sha1) names; the service never infers the hash
 * algorithm from a path, so both coexist. Deletion is reference-checked: a content-addressed file may be shared by
 * several entities, so {@see remove()} unlinks only when no {@see FileReferenceProviderInterface} still claims it
 * (GH-583).
 *
 * The service is stateless and worker-safe: it holds no request- or user-scoped state.
 */
final readonly class FileStorage
{
    public function __construct(
        private FilesystemOperator $defaultStorage,
        /** @var iterable<FileReferenceProviderInterface> */
        #[AutowireIterator('app.file_reference_provider')]
        private iterable $referenceProviders = [],
    ) {
    }

    /**
     * Validate and store a local file into the given namespace, content-addressed and de-duplicated. $localPath is a
     * readable path on the real filesystem (an uploaded temp file, a fixture asset); $scope supplies the per-entity
     * segment for scoped namespaces (e.g. the company id).
     *
     * @throws FileStorageException when the source is unreadable, its detected MIME type is not accepted by the
     *                              namespace, or it exceeds the namespace size limit.
     */
    public function store(
        StorageNamespace $namespace,
        string $localPath,
        ?string $scope = null,
    ): StoredFile {
        if (
            !is_file($localPath)
            || !is_readable($localPath)
        ) {
            throw new FileStorageException(sprintf('Cannot read file to store: "%s".', $localPath));
        }

        $size = filesize($localPath);
        if (
            false === $size
            || 0 === $size
        ) {
            throw new FileStorageException(sprintf('Refusing to store an empty or unreadable file: "%s".', $localPath));
        }

        if ($size > $namespace->maxFileSizeBytes()) {
            throw new FileStorageException(sprintf(
                'File of %d bytes exceeds the %d byte limit for namespace "%s".',
                $size,
                $namespace->maxFileSizeBytes(),
                $namespace->value,
            ));
        }

        $mimeType = MimeTypes::getDefault()->guessMimeType($localPath);
        if (
            null === $mimeType
            || !$namespace->acceptsMimeType($mimeType)
        ) {
            throw new FileStorageException(sprintf(
                'MIME type "%s" is not accepted by namespace "%s".',
                $mimeType ?? 'unknown',
                $namespace->value,
            ));
        }

        $hash = hash_file(
            'sha256',
            $localPath,
        );
        if (false === $hash) {
            throw new FileStorageException(sprintf('Cannot hash file to store: "%s".', $localPath));
        }

        $path = $this->buildPath(
            $namespace,
            $scope,
            $hash,
            $mimeType,
        );

        if ($this->defaultStorage->fileExists($path)) {
            return new StoredFile(
                $path,
                $hash,
                $mimeType,
                $size,
                true,
            );
        }

        $stream = fopen(
            $localPath,
            'rb',
        );
        if (false === $stream) {
            throw new FileStorageException(sprintf('Cannot open file to store: "%s".', $localPath));
        }

        try {
            $this->defaultStorage->writeStream(
                $path,
                $stream,
            );
        } finally {
            fclose($stream);
        }

        return new StoredFile(
            $path,
            $hash,
            $mimeType,
            $size,
            false,
        );
    }

    /**
     * Whether a file exists at the given stored path.
     */
    public function exists(string $path): bool
    {
        return $this->defaultStorage->fileExists($path);
    }

    /**
     * Open a read stream for the given stored path.
     *
     * @return resource
     */
    public function readStream(string $path)
    {
        return $this->defaultStorage->readStream($path);
    }

    /**
     * Read the full contents of the given stored path.
     */
    public function read(string $path): string
    {
        return $this->defaultStorage->read($path);
    }

    /**
     * The size, in bytes, of the file at the given stored path.
     */
    public function fileSize(string $path): int
    {
        return $this->defaultStorage->fileSize($path);
    }

    /**
     * Write raw contents to an exact stored path (not content-addressed). Used by the variant pipeline and cover
     * generator, which own their target paths.
     */
    public function write(
        string $path,
        string $contents,
    ): void {
        $this->defaultStorage->write(
            $path,
            $contents,
        );
    }

    /**
     * Stream contents to an exact stored path (not content-addressed).
     *
     * @param resource $stream
     */
    public function writeStream(
        string $path,
        $stream,
    ): void {
        $this->defaultStorage->writeStream(
            $path,
            $stream,
        );
    }

    /**
     * Move a file between two stored paths, overwriting the destination. Enables atomic "write to a temp path, then
     * move into place" so a partially generated variant is never visible.
     */
    public function move(
        string $source,
        string $destination,
    ): void {
        $this->defaultStorage->move(
            $source,
            $destination,
        );
    }

    /**
     * Delete the file at the given stored path, but only if no domain still references it (GH-583). Returns whether the
     * file was actually removed (or was already absent); `false` means another entity still points at the shared bytes.
     *
     * Call this only after removing your own referencing entity and flushing, so the providers see committed state.
     */
    public function remove(string $path): bool
    {
        foreach ($this->referenceProviders as $provider) {
            if ($provider->references($path)) {
                return false;
            }
        }

        if ($this->defaultStorage->fileExists($path)) {
            $this->defaultStorage->delete($path);
        }

        return true;
    }

    /**
     * Build the content-addressed storage path for a new file: namespace directory, an optional two-character shard,
     * then `{hash}.{ext}` where the extension is canonicalised from the detected MIME type (so identical content always
     * maps to one path regardless of the uploaded filename).
     */
    private function buildPath(
        StorageNamespace $namespace,
        ?string $scope,
        string $hash,
        string $mimeType,
    ): string {
        $directory = $namespace->directory($scope);

        return sprintf(
            '%s/%s.%s',
            $directory,
            $hash,
            $this->extensionForMimeType($mimeType),
        );
    }

    /**
     * The canonical file extension for a stored MIME type. Only the types the namespaces whitelist need to appear here.
     */
    private function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }
}
