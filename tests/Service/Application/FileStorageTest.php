<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\StorageNamespace;
use App\Service\Application\FileReferenceProviderInterface;
use App\Service\Application\FileStorage;
use App\Service\Application\FileStorageException;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Override;
use PHPUnit\Framework\TestCase;

use function base64_decode;
use function fclose;
use function file_put_contents;
use function fopen;
use function ftruncate;
use function hash;
use function str_starts_with;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Exercises the content-addressed storage service against a real in-memory flysystem adapter (the same adapter the
 * suite uses under `when@test`), so the path derivation, de-duplication, per-namespace validation and reference-checked
 * deletion are covered without touching disk.
 */
final class FileStorageTest extends TestCase
{
    /** A minimal but valid 1x1 PNG, so MIME sniffing recognises it as image/png. */
    private const string PNG_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /** @var list<string> */
    private array $tempFiles = [];

    #[Override]
    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        $this->tempFiles = [];
    }

    public function testStoreDerivesScopedContentAddressedPath(): void
    {
        $storage = $this->storage();
        $source = $this->pngFixture();

        $stored = $storage->store(
            StorageNamespace::PhotoOriginal,
            $source,
            '1',
        );

        $expectedHash = hash(
            'sha256',
            $this->pngBytes(),
        );
        self::assertSame(
            $expectedHash,
            $stored->hash,
        );
        self::assertSame(
            'image/png',
            $stored->mimeType,
        );
        self::assertFalse($stored->deduplicated);
        self::assertSame(
            'photos/albums/1/' . $expectedHash . '.png',
            $stored->path,
        );
        self::assertTrue($storage->exists($stored->path));
    }

    public function testStoreDeduplicatesIdenticalContent(): void
    {
        $storage = $this->storage();

        $first = $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->pngFixture(),
            '1',
        );
        $second = $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->pngFixture(),
            '1',
        );

        self::assertFalse($first->deduplicated);
        self::assertTrue($second->deduplicated);
        self::assertSame(
            $first->path,
            $second->path,
        );
    }

    public function testStoreIntoScopedNamespaceBuildsPerCompanyPath(): void
    {
        $storage = $this->storage();

        $stored = $storage->store(
            StorageNamespace::CompanyImage,
            $this->pngFixture(),
            '42',
        );

        self::assertTrue(str_starts_with($stored->path, 'career/42/images/'));
        // Company assets are scoped per company id.
        self::assertSame(
            'career/42/images/' . $stored->hash . '.png',
            $stored->path,
        );
    }

    public function testStoreRejectsDisallowedMimeType(): void
    {
        $storage = $this->storage();
        $textFile = $this->tempFile();
        file_put_contents(
            $textFile,
            'this is definitely not an image',
        );

        $this->expectException(FileStorageException::class);
        $storage->store(
            StorageNamespace::PhotoOriginal,
            $textFile,
            '1',
        );
    }

    public function testStoreRejectsEmptyFile(): void
    {
        $storage = $this->storage();

        $this->expectException(FileStorageException::class);
        $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->tempFile(),
            '1',
        );
    }

    public function testStoreRejectsOversizedFile(): void
    {
        $storage = $this->storage();
        $big = $this->tempFile();
        $handle = fopen(
            $big,
            'wb',
        );
        self::assertIsResource($handle);
        // A sparse file one byte over the namespace limit: filesize() reports it as huge, but it costs no real disk.
        ftruncate(
            $handle,
            StorageNamespace::PhotoOriginal->maxFileSizeBytes() + 1,
        );
        fclose($handle);

        $this->expectException(FileStorageException::class);
        $storage->store(
            StorageNamespace::PhotoOriginal,
            $big,
            '1',
        );
    }

    public function testRemoveDeletesWhenNothingReferencesTheFile(): void
    {
        $storage = $this->storage();
        $stored = $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->pngFixture(),
            '1',
        );

        self::assertTrue($storage->remove($stored->path));
        self::assertFalse($storage->exists($stored->path));
    }

    public function testRemoveKeepsFileWhileAnotherEntityReferencesIt(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $claiming = new class implements FileReferenceProviderInterface {
            #[Override]
            public function references(string $path): bool
            {
                return true;
            }
        };
        $storage = new FileStorage(
            $filesystem,
            [$claiming],
        );
        $stored = $storage->store(
            StorageNamespace::PhotoOriginal,
            $this->pngFixture(),
            '1',
        );

        self::assertFalse($storage->remove($stored->path));
        self::assertTrue($storage->exists($stored->path));
    }

    private function storage(): FileStorage
    {
        return new FileStorage(new Filesystem(new InMemoryFilesystemAdapter()));
    }

    private function pngFixture(): string
    {
        $path = $this->tempFile();
        file_put_contents(
            $path,
            $this->pngBytes(),
        );

        return $path;
    }

    private function pngBytes(): string
    {
        $bytes = base64_decode(
            self::PNG_BASE64,
            true,
        );
        self::assertIsString($bytes);

        return $bytes;
    }

    private function tempFile(): string
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'gewisweb-filestorage-test',
        );
        self::assertIsString($path);
        $this->tempFiles[] = $path;

        return $path;
    }
}
