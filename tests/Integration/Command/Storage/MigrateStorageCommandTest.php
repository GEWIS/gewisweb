<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Storage;

use App\Command\Storage\MigrateStorageCommand;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Career\CompanyRevision;
use App\Repository\Career\CompanyBannerPackageRepository;
use App\Repository\Career\CompanyRevisionRepository;
use App\Repository\Decision\OrganInformationRepository;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Tests\Integration\DatabaseTestCase;
use Override;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

use function glob;
use function sys_get_temp_dir;
use function uniqid;

/**
 * The storage migration is a one-shot production command, so its two phases are pinned end to end. The path-mapping
 * transformation is asserted directly for every namespace (it is the shared core of both phases); the file-linking and
 * path-rewriting phases are driven through {@see CommandTester} against a synthetic legacy tree in a temp project dir
 * (so the hardlinks and the rollback log never touch the real checkout), while the database writes run against the
 * seeded MariaDB and are rolled back by dama.
 *
 * A seeded {@see CompanyRevision} is used as the migratable row because it is the simplest entity with a scoped
 * (per-company) path and no filesystem-touching lifecycle callbacks.
 */
final class MigrateStorageCommandTest extends DatabaseTestCase
{
    private string $projectDir;

    private Filesystem $filesystem;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->projectDir = sys_get_temp_dir() . '/storage-migration-' . uniqid();
        $this->filesystem->mkdir([
            $this->projectDir . '/public/data',
            $this->projectDir . '/data',
            $this->projectDir . '/var',
        ]);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);

        parent::tearDown();
    }

    public function testMapsLegacyPathsToTheNewLayoutForEveryNamespace(): void
    {
        $command = $this->command();

        // The new layout is not sharded, so the legacy `{2ch}/` bucket is dropped and only the sha-named file is
        // re-rooted. Photos and covers are scoped per album.
        self::assertSame(
            'photos/albums/7/cfceb39d57d914ed8b14d0e37643de0797ae56.jpg',
            $command->mapLegacyPath(
                StorageNamespace::PhotoOriginal,
                '92/cfceb39d57d914ed8b14d0e37643de0797ae56.jpg',
                '7',
            ),
        );
        self::assertSame(
            'photos/covers/7/2b3c4d.png',
            $command->mapLegacyPath(
                StorageNamespace::PhotoCover,
                '1a/2b3c4d.png',
                '7',
            ),
        );
        self::assertSame(
            'organs/images/2b3c4d.jpg',
            $command->mapLegacyPath(
                StorageNamespace::OrganImage,
                '1a/2b3c4d.jpg',
            ),
        );

        // The scoped company namespace drops the legacy `company/{id}/` prefix (and the shard) and re-roots the file
        // under `career/{id}/images`.
        self::assertSame(
            'career/42/images/2b3c4d.png',
            $command->mapLegacyPath(
                StorageNamespace::CompanyImage,
                'company/42/1a/2b3c4d.png',
                '42',
            ),
        );

        // Idempotent: an already-migrated value maps to null (skip), so re-running is safe.
        self::assertNull(
            $command->mapLegacyPath(
                StorageNamespace::PhotoOriginal,
                'photos/albums/7/cfceb39d57d914ed8b14d0e37643de0797ae56.jpg',
                '7',
            ),
        );
        self::assertNull(
            $command->mapLegacyPath(
                StorageNamespace::CompanyImage,
                'career/42/images/1a/2b3c4d.png',
                '42',
            ),
        );

        // An unrecognised company path is left untouched (never guessed at).
        self::assertNull(
            $command->mapLegacyPath(
                StorageNamespace::CompanyImage,
                'data/company/banner/foo.png',
                '42',
            ),
        );
    }

    public function testDryRunReportsButChangesNothing(): void
    {
        $revision = $this->aCompanyRevision();
        $companyId = $revision->getCompany()->getId();
        self::assertNotNull($companyId);
        $revisionId = $revision->getId();
        self::assertNotNull($revisionId);

        $legacy = 'company/' . $companyId . '/ab/cafebabecafebabe.png';
        $revision->setLogo($legacy);
        $this->entityManager->flush();

        // A path dry run leaves the stored value alone and writes no rollback log.
        $this->runCommand(['--paths' => true, '--dry-run' => true]);

        $reloaded = $this->entityManager->getRepository(CompanyRevision::class)->find($revisionId);
        self::assertInstanceOf(
            CompanyRevision::class,
            $reloaded,
        );
        self::assertSame(
            $legacy,
            $reloaded->getLogo(),
        );
        self::assertEmpty($this->logFiles());

        // A file dry run creates no hardlink even when the legacy source is present.
        $source = $this->projectDir . '/public/data/' . $legacy;
        $this->filesystem->dumpFile(
            $source,
            'binary',
        );

        $this->runCommand(['--files' => true, '--dry-run' => true]);

        self::assertFileDoesNotExist(
            $this->projectDir . '/data/career/' . $companyId . '/images/cafebabecafebabe.png',
        );
    }

    public function testFilesPhaseHardlinksIdempotently(): void
    {
        $revision = $this->aCompanyRevision();
        $companyId = $revision->getCompany()->getId();
        self::assertNotNull($companyId);

        $legacy = 'company/' . $companyId . '/cd/0badc0de0badc0de.png';
        $revision->setLogo($legacy);
        $this->entityManager->flush();

        $source = $this->projectDir . '/public/data/' . $legacy;
        $this->filesystem->dumpFile(
            $source,
            'image-bytes',
        );
        $destination = $this->projectDir . '/data/career/' . $companyId . '/images/0badc0de0badc0de.png';

        $this->runCommand(['--files' => true]);
        self::assertFileExists($destination);

        // Re-running must be safe: the destination already exists, so it is skipped rather than erroring.
        $this->runCommand(['--files' => true]);
        self::assertFileExists($destination);

        // The file phase is non-destructive: it never touches the database path.
        $reloaded = $this->entityManager->getRepository(CompanyRevision::class)->find($revision->getId());
        self::assertInstanceOf(
            CompanyRevision::class,
            $reloaded,
        );
        self::assertSame(
            $legacy,
            $reloaded->getLogo(),
        );
    }

    public function testPathsPhaseRewritesAndRollbackRestores(): void
    {
        $revision = $this->aCompanyRevision();
        $companyId = $revision->getCompany()->getId();
        self::assertNotNull($companyId);
        $revisionId = $revision->getId();
        self::assertNotNull($revisionId);

        $legacy = 'company/' . $companyId . '/ef/deadbeefdeadbeef.png';
        $expected = 'career/' . $companyId . '/images/deadbeefdeadbeef.png';
        $revision->setLogo($legacy);
        $this->entityManager->flush();

        // Switch-over: the stored path is rewritten and a rollback log is recorded.
        $this->runCommand(['--paths' => true]);

        $migrated = $this->entityManager->getRepository(CompanyRevision::class)->find($revisionId);
        self::assertInstanceOf(
            CompanyRevision::class,
            $migrated,
        );
        self::assertSame(
            $expected,
            $migrated->getLogo(),
        );
        self::assertNotEmpty($this->logFiles());

        // Rollback restores the original legacy path from the log.
        $this->runCommand(['--paths' => true, '--rollback' => true]);

        $restored = $this->entityManager->getRepository(CompanyRevision::class)->find($revisionId);
        self::assertInstanceOf(
            CompanyRevision::class,
            $restored,
        );
        self::assertSame(
            $legacy,
            $restored->getLogo(),
        );
    }

    /**
     * @param array<string, bool|string> $input
     */
    private function runCommand(array $input): CommandTester
    {
        $tester = new CommandTester($this->command());
        // Non-interactive, so the destructive-run confirmation falls through to its "proceed" default (as under cron).
        $tester->execute(
            $input,
            ['interactive' => false],
        );
        $tester->assertCommandIsSuccessful();

        return $tester;
    }

    /**
     * A fresh command bound to the temp project dir, so its filesystem work and rollback log stay out of the checkout.
     */
    private function command(): MigrateStorageCommand
    {
        return new MigrateStorageCommand(
            $this->entityManager,
            self::getContainer()->get(PhotoRepository::class),
            self::getContainer()->get(AlbumRepository::class),
            self::getContainer()->get(CompanyRevisionRepository::class),
            self::getContainer()->get(CompanyBannerPackageRepository::class),
            self::getContainer()->get(OrganInformationRepository::class),
            $this->projectDir,
        );
    }

    private function aCompanyRevision(): CompanyRevision
    {
        $revision = $this->entityManager->getRepository(CompanyRevision::class)->findOneBy([]);
        self::assertInstanceOf(
            CompanyRevision::class,
            $revision,
            'The seed is expected to contain at least one company revision.',
        );

        return $revision;
    }

    /**
     * @return list<string>
     */
    private function logFiles(): array
    {
        $matches = glob($this->projectDir . '/var/storage-migration/paths-*.jsonl');

        return false === $matches
            ? []
            : $matches;
    }
}
