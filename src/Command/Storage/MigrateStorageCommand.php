<?php

declare(strict_types=1);

namespace App\Command\Storage;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyRevision;
use App\Entity\Decision\OrganInformation;
use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Repository\Career\CompanyBannerPackageRepository;
use App\Repository\Career\CompanyRevisionRepository;
use App\Repository\Decision\OrganInformationRepository;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use JsonException;
use Override;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function assert;
use function count;
use function date;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function intval;
use function is_array;
use function is_dir;
use function is_file;
use function is_int;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function link;
use function mkdir;
use function sort;
use function sprintf;
use function str_starts_with;
use function strval;
use function trim;

use const FILE_APPEND;
use const JSON_THROW_ON_ERROR;
use const LOCK_EX;

/**
 * One-shot migration from the legacy Laminas file layout to the content-addressed layout served by
 * {@see \App\Service\Application\FileStorage}.
 *
 * Two layouts coexist during the cut-over:
 *  - LEGACY: everything lived under `public/data/`. Hashed assets (album photo originals, generated album covers and
 *    organ cover/thumbnail images) were stored at `public/data/{2ch}/{rest-of-sha1}.{ext}`; per-company assets (the
 *    company logo and banner-package image) at `public/data/company/{companyId}/{2ch}/{rest-of-sha1}.{ext}`. The DB
 *    columns hold the path relative to `public/data/`.
 *  - NEW: everything lives under `data/` (never web-reachable), partitioned per {@see StorageNamespace}, e.g.
 *    `data/photos/albums/{2ch}/`, `data/photos/covers/`, `data/organs/images/`, `data/career/{companyId}/images/`.
 *
 * The migration keeps the existing (sha1) filenames (it never re-hashes), so it is instant and adds no disk. It runs
 * in two independent, re-runnable phases:
 *  - `--files` hardlinks each legacy file into its new location (both layouts stay live; nothing is ever deleted).
 *  - `--paths` rewrites the DB path columns to the new layout (the actual switch-over), recording a rollback log.
 *
 * Both phases derive the new location from the legacy value with the exact same mapping ({@see mapLegacyPath()}), so a
 * row's rewritten path always points at a file `--files` created. `--dry-run` reports without changing anything, and
 * `--rollback` restores the DB paths from a `--paths` run's log.
 *
 * @psalm-type StorageTarget = array{
 *     key: string,
 *     field: string,
 *     namespace: StorageNamespace,
 * }
 * @psalm-type MigrationRow = array{
 *     key: string,
 *     entity: object,
 *     field: string,
 *     legacy: string,
 *     new: string,
 * }
 * @psalm-type LogEntry = array{
 *     key: string,
 *     id: int,
 *     old: string,
 *     new: string,
 * }
 */
#[AsCommand(
    name: 'app:storage:migrate',
    description: 'Migrate stored files and their paths from the legacy public/data layout to the new data/ layout.',
)]
final class MigrateStorageCommand extends Command
{
    /** Stable per-column identifiers, persisted in the rollback log so a restore never depends on class names. */
    private const string KEY_PHOTO = 'photo-original';
    private const string KEY_ALBUM_COVER = 'album-cover';
    private const string KEY_COMPANY_LOGO = 'company-logo';
    private const string KEY_COMPANY_BANNER = 'company-banner';
    private const string KEY_ORGAN_COVER = 'organ-cover';
    private const string KEY_ORGAN_THUMBNAIL = 'organ-thumbnail';

    /** Outcomes of a single hardlink attempt. */
    private const string LINK_LINKED = 'linked';
    private const string LINK_SKIPPED = 'skipped';
    private const string LINK_MISSING_SOURCE = 'missing';

    /** How many legacy-to-new pairs to show in the report, and how often a read-only pass detaches managed entities. */
    private const int SAMPLE_SIZE = 10;
    private const int CLEAR_EVERY = 500;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PhotoRepository $photoRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly CompanyRevisionRepository $companyRevisionRepository,
        private readonly CompanyBannerPackageRepository $companyBannerPackageRepository,
        private readonly OrganInformationRepository $organInformationRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'files',
                null,
                InputOption::VALUE_NONE,
                'Hardlink the legacy files into the new data/ layout (non-destructive, both layouts stay live).',
            )
            ->addOption(
                'paths',
                null,
                InputOption::VALUE_NONE,
                'Rewrite the stored path columns from the legacy layout to the new one (the switch-over).',
            )
            ->addOption(
                'rollback',
                null,
                InputOption::VALUE_NONE,
                'With --paths: restore the stored paths from a previous run using its rollback log.',
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Report what would happen without changing anything.',
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Flush and clear the entity manager every N path rewrites.',
                '500',
            )
            ->addOption(
                'log',
                null,
                InputOption::VALUE_REQUIRED,
                'With --rollback: the log file to restore from (defaults to the most recent one).',
            );
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $ui = new SymfonyStyle(
            $input,
            $output,
        );

        $files = true === $input->getOption('files');
        $paths = true === $input->getOption('paths');
        $rollback = true === $input->getOption('rollback');
        $dryRun = true === $input->getOption('dry-run');

        if (
            !$files
            && !$paths
        ) {
            $ui->error('Specify the phase to run: --files or --paths.');

            return Command::FAILURE;
        }

        if (
            $files
            && $paths
        ) {
            $ui->error('Choose a single phase: either --files or --paths, not both.');

            return Command::FAILURE;
        }

        if (
            $files
            && $rollback
        ) {
            $ui->error('--rollback only applies to --paths; hardlinks are non-destructive and need no rollback.');

            return Command::FAILURE;
        }

        if ($files) {
            return $this->migrateFiles(
                $ui,
                $dryRun,
            );
        }

        if ($rollback) {
            if (
                !$this->confirmDestructive(
                    $ui,
                    $input,
                    $dryRun,
                    'restore the stored paths from the rollback log',
                )
            ) {
                return Command::SUCCESS;
            }

            return $this->rollback(
                $ui,
                $dryRun,
                $this->stringOption(
                    $input,
                    'log',
                ),
            );
        }

        $batchSize = $this->batchSize(
            $ui,
            $input,
        );
        if (null === $batchSize) {
            return Command::FAILURE;
        }

        if (
            !$this->confirmDestructive(
                $ui,
                $input,
                $dryRun,
                'rewrite the stored paths in the database',
            )
        ) {
            return Command::SUCCESS;
        }

        return $this->migratePaths(
            $ui,
            $dryRun,
            $batchSize,
        );
    }

    /**
     * Map a legacy stored path onto its location in the new layout, or return null when the value is already migrated
     * or is not a recognised legacy path (both cases are left untouched, which makes the migration idempotent). This is
     * the mapping shared by the file-linking and path-rewriting phases.
     */
    public function mapLegacyPath(
        StorageNamespace $namespace,
        string $legacyPath,
        ?string $scope = null,
    ): ?string {
        $targetDirectory = $namespace->directory($scope);

        if (StorageNamespace::CompanyImage === $namespace) {
            return $this->mapLegacyCompanyPath(
                $legacyPath,
                $targetDirectory,
            );
        }

        // Non-scoped namespaces (photo originals, covers, organ images): the stored value is already the sha1 shard
        // plus filename relative to `public/data/`. Keep it verbatim, only re-rooting it under the new namespace
        // directory. If it already sits there, the row was migrated before, so skip it.
        if (
            str_starts_with(
                $legacyPath,
                $targetDirectory . '/',
            )
        ) {
            return null;
        }

        return $targetDirectory . '/' . $legacyPath;
    }

    /**
     * Map a legacy per-company path (`company/{companyId}/{shard}/{name}.{ext}`) onto the per-company career namespace,
     * preserving the sha1 shard and filename.
     */
    private function mapLegacyCompanyPath(
        string $legacyPath,
        string $targetDirectory,
    ): ?string {
        // Already migrated: it points at the new per-company career directory.
        if (
            str_starts_with(
                $legacyPath,
                'career/',
            )
        ) {
            return null;
        }

        // Anything that is not a recognised legacy company path (e.g. a synthetic seed value) is left untouched.
        // The migration never guesses where an unfamiliar file belongs.
        if (
            !str_starts_with(
                $legacyPath,
                'company/',
            )
        ) {
            return null;
        }

        $tail = $this->stripCompanyPrefix($legacyPath);
        if (null === $tail) {
            return null;
        }

        return $targetDirectory . '/' . $tail;
    }

    /**
     * Strip the `company/{companyId}/` prefix off a legacy per-company path, returning the `{shard}/{name}.{ext}` tail
     * (or null when the path does not have that shape).
     */
    private function stripCompanyPrefix(string $legacyPath): ?string
    {
        $segments = explode(
            '/',
            $legacyPath,
            3,
        );

        if (
            3 !== count($segments)
            || '' === $segments[2]
        ) {
            return null;
        }

        return $segments[2];
    }

    /**
     * Hardlink every legacy file into its new location. Idempotent: an already-present destination is skipped and a
     * missing legacy source is reported, never fatal for the run.
     */
    private function migrateFiles(
        SymfonyStyle $ui,
        bool $dryRun,
    ): int {
        $ui->section($dryRun ? 'Linking legacy files (dry run)' : 'Linking legacy files into the new layout');

        $linked = 0;
        $skipped = 0;
        $missing = 0;
        /** @var list<array{0: string, 1: string}> $sample */
        $sample = [];
        $processed = 0;

        foreach ($this->migratableRows() as $row) {
            $source = $this->legacyRoot() . '/' . $row['legacy'];
            $destination = $this->newRoot() . '/' . $row['new'];

            if ($dryRun) {
                if (!is_file($source)) {
                    $missing++;
                } elseif (file_exists($destination)) {
                    $skipped++;
                } else {
                    $linked++;
                }
            } else {
                $status = $this->linkFile(
                    $source,
                    $destination,
                );

                if (self::LINK_LINKED === $status) {
                    $linked++;
                } elseif (self::LINK_SKIPPED === $status) {
                    $skipped++;
                } else {
                    $missing++;
                }
            }

            if (count($sample) < self::SAMPLE_SIZE) {
                $sample[] = [
                    $row['legacy'],
                    $row['new'],
                ];
            }

            // Read-only pass: periodically detach managed entities so memory stays flat over a large photo set.
            if (0 !== ++$processed % self::CLEAR_EVERY) {
                continue;
            }

            $this->entityManager->clear();
        }

        $this->entityManager->clear();

        $this->reportSample(
            $ui,
            $sample,
        );
        $ui->success(sprintf(
            '%s %d file(s); skipped %d already present; %d legacy source(s) missing.',
            $dryRun ? 'Would link' : 'Linked',
            $linked,
            $skipped,
            $missing,
        ));

        return Command::SUCCESS;
    }

    /**
     * Rewrite the stored path columns to the new layout in batches, writing a rollback log as it goes.
     *
     * Each batch's log entries are appended to the log file before that batch is committed to the database (a
     * write-ahead log). If the process dies mid-run, every committed rewrite is therefore already in the log, and any
     * entry logged for a batch that was not committed is harmless because rollback() only restores a row that still
     * points at the migrated value.
     */
    private function migratePaths(
        SymfonyStyle $ui,
        bool $dryRun,
        int $batchSize,
    ): int {
        $ui->section($dryRun ? 'Rewriting stored paths (dry run)' : 'Rewriting stored paths');

        $rewritten = 0;
        /** @var list<array{0: string, 1: string}> $sample */
        $sample = [];
        /** @var list<LogEntry> $pending */
        $pending = [];
        $logFile = $dryRun
            ? null
            : $this->newLogFile();
        $processed = 0;

        foreach ($this->migratableRows() as $row) {
            $rewritten++;

            if (count($sample) < self::SAMPLE_SIZE) {
                $sample[] = [
                    $row['legacy'],
                    $row['new'],
                ];
            }

            if (null !== $logFile) {
                $pending[] = [
                    'key' => $row['key'],
                    'id' => $this->entityId($row['entity']),
                    'old' => $row['legacy'],
                    'new' => $row['new'],
                ];
                $this->writePathField(
                    $row['entity'],
                    $row['field'],
                    $row['new'],
                );
            }

            if (0 !== ++$processed % $batchSize) {
                continue;
            }

            if (null !== $logFile) {
                $this->appendLog(
                    $logFile,
                    $pending,
                );
                $pending = [];
                $this->entityManager->flush();
            }

            $this->entityManager->clear();
        }

        $this->reportSample(
            $ui,
            $sample,
        );

        if (null === $logFile) {
            $ui->success(sprintf('Would rewrite %d stored path(s).', $rewritten));

            return Command::SUCCESS;
        }

        // Log and commit the final partial batch.
        $this->appendLog(
            $logFile,
            $pending,
        );
        $this->entityManager->flush();
        $this->entityManager->clear();

        if (0 === $rewritten) {
            $ui->success('No legacy stored paths were found; nothing to rewrite.');

            return Command::SUCCESS;
        }

        $ui->success(sprintf(
            'Rewrote %d stored path(s). Rollback log written to "%s".',
            $rewritten,
            $logFile,
        ));

        return Command::SUCCESS;
    }

    /**
     * Restore stored paths from a `--paths` rollback log. A row is only restored while it still points at the migrated
     * value, so a path changed since the migration is never clobbered.
     */
    private function rollback(
        SymfonyStyle $ui,
        bool $dryRun,
        ?string $logOption,
    ): int {
        $logFile = $this->resolveLogFile($logOption);
        if (null === $logFile) {
            $ui->error('No rollback log was found under var/storage-migration; nothing to roll back.');

            return Command::FAILURE;
        }

        $ui->section(sprintf(
            '%sRolling back stored paths from "%s"',
            $dryRun ? '(dry run) ' : '',
            $logFile,
        ));

        $restored = 0;
        $skipped = 0;
        $processed = 0;

        foreach ($this->readLog($logFile) as $entry) {
            $field = $this->fieldForKey($entry['key']);
            $entity = $this->findByKey(
                $entry['key'],
                $entry['id'],
            );

            if (
                null === $field
                || null === $entity
                || $this->readPathField(
                    $entity,
                    $field,
                ) !== $entry['new']
            ) {
                $skipped++;

                continue;
            }

            $restored++;

            if ($dryRun) {
                continue;
            }

            $this->writePathField(
                $entity,
                $field,
                $entry['old'],
            );

            if (0 !== ++$processed % self::CLEAR_EVERY) {
                continue;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        if (!$dryRun) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $ui->success(sprintf(
            '%s %d stored path(s); skipped %d (missing, unknown, or changed since).',
            $dryRun ? 'Would restore' : 'Restored',
            $restored,
            $skipped,
        ));

        return Command::SUCCESS;
    }

    /**
     * Yield every migratable row across all storage columns, each with its resolved legacy and new path. Rows that are
     * already migrated, unrecognised, or without a scope are skipped here so the phases never see them.
     *
     * Each column is streamed with `toIterable()`, so a consumer may flush and clear the entity manager between rows to
     * keep memory flat over a large photo set; clearing while this generator is suspended is safe (the database cursor
     * is independent of the identity map).
     *
     * @return Generator<int, MigrationRow>
     */
    private function migratableRows(): Generator
    {
        foreach ($this->targets() as $target) {
            $namespace = $target['namespace'];
            $field = $target['field'];
            $key = $target['key'];

            foreach ($this->entitiesFor($key) as $entity) {
                assert(is_object($entity));

                $legacy = $this->readPathField(
                    $entity,
                    $field,
                );
                if (
                    null === $legacy
                    || '' === $legacy
                ) {
                    continue;
                }

                $scope = $this->resolveScope($entity);
                if (
                    StorageNamespace::CompanyImage === $namespace
                    && null === $scope
                ) {
                    continue;
                }

                $new = $this->mapLegacyPath(
                    $namespace,
                    $legacy,
                    $scope,
                );
                if (null === $new) {
                    continue;
                }

                yield [
                    'key' => $key,
                    'entity' => $entity,
                    'field' => $field,
                    'legacy' => $legacy,
                    'new' => $new,
                ];
            }
        }
    }

    /**
     * The six storage columns to migrate, each with its path field and target namespace. The matching entities are
     * streamed separately by {@see entitiesFor()} (keyed off the stable key), so the generic Doctrine query type never
     * has to be pinned in a shared type alias.
     *
     * @return list<StorageTarget>
     */
    private function targets(): array
    {
        return [
            [
                'key' => self::KEY_PHOTO,
                'field' => 'path',
                'namespace' => StorageNamespace::PhotoOriginal,
            ],
            [
                'key' => self::KEY_ALBUM_COVER,
                'field' => 'coverPath',
                'namespace' => StorageNamespace::PhotoCover,
            ],
            [
                'key' => self::KEY_COMPANY_LOGO,
                'field' => 'logo',
                'namespace' => StorageNamespace::CompanyImage,
            ],
            [
                'key' => self::KEY_COMPANY_BANNER,
                'field' => 'image',
                'namespace' => StorageNamespace::CompanyImage,
            ],
            [
                'key' => self::KEY_ORGAN_COVER,
                'field' => 'coverPath',
                'namespace' => StorageNamespace::OrganImage,
            ],
            [
                'key' => self::KEY_ORGAN_THUMBNAIL,
                'field' => 'thumbnailPath',
                'namespace' => StorageNamespace::OrganImage,
            ],
        ];
    }

    /**
     * Stream the entities of the column identified by $key, one at a time (`toIterable()`), so the caller can flush and
     * clear the entity manager between rows. The value type is left fully open on purpose: the two static analysers
     * infer different generic types for a Doctrine query, so the concrete row type is asserted at the call site.
     *
     * @return iterable<mixed, mixed>
     */
    private function entitiesFor(string $key): iterable
    {
        $repository = match ($key) {
            self::KEY_PHOTO => $this->photoRepository,
            self::KEY_ALBUM_COVER => $this->albumRepository,
            self::KEY_COMPANY_LOGO => $this->companyRevisionRepository,
            self::KEY_COMPANY_BANNER => $this->companyBannerPackageRepository,
            self::KEY_ORGAN_COVER, self::KEY_ORGAN_THUMBNAIL => $this->organInformationRepository,
            default => throw new RuntimeException(sprintf('Unknown storage target "%s".', $key)),
        };

        return $repository->createQueryBuilder('e')->getQuery()->toIterable();
    }

    /**
     * Read the current stored value of the given path field off an entity.
     */
    private function readPathField(
        object $entity,
        string $field,
    ): ?string {
        switch (true) {
            case $entity instanceof Photo:
                return $entity->getPath();

            case $entity instanceof Album:
                return $entity->getCoverPath();

            case $entity instanceof CompanyRevision:
                return $entity->getLogo();

            case $entity instanceof CompanyBannerPackage:
                return $entity->getImage();

            case $entity instanceof OrganInformation:
                return 'thumbnailPath' === $field
                    ? $entity->getThumbnailPath()
                    : $entity->getCoverPath();

            default:
                throw new RuntimeException(sprintf('Cannot read a storage path from "%s".', $entity::class));
        }
    }

    /**
     * Write a new value into the given path field of an entity.
     */
    private function writePathField(
        object $entity,
        string $field,
        string $value,
    ): void {
        switch (true) {
            case $entity instanceof Photo:
                $entity->setPath($value);

                return;

            case $entity instanceof Album:
                $entity->setCoverPath($value);

                return;

            case $entity instanceof CompanyRevision:
                $entity->setLogo($value);

                return;

            case $entity instanceof CompanyBannerPackage:
                $entity->setImage($value);

                return;

            case $entity instanceof OrganInformation:
                if ('thumbnailPath' === $field) {
                    $entity->setThumbnailPath($value);
                } else {
                    $entity->setCoverPath($value);
                }

                return;

            default:
                throw new RuntimeException(sprintf('Cannot write a storage path to "%s".', $entity::class));
        }
    }

    /**
     * The per-company scope (the company id, as a string) for a scoped entity, or null for the non-scoped ones.
     */
    private function resolveScope(object $entity): ?string
    {
        if ($entity instanceof CompanyRevision) {
            $id = $entity->getCompany()->getId();
        } elseif ($entity instanceof CompanyBannerPackage) {
            $id = $entity->getCompany()->getId();
        } else {
            return null;
        }

        return null === $id
            ? null
            : strval($id);
    }

    /**
     * The path field a log key refers to, or null when the key is unknown (e.g. a log from a newer version).
     */
    private function fieldForKey(string $key): ?string
    {
        return match ($key) {
            self::KEY_PHOTO => 'path',
            self::KEY_ALBUM_COVER => 'coverPath',
            self::KEY_COMPANY_LOGO => 'logo',
            self::KEY_COMPANY_BANNER => 'image',
            self::KEY_ORGAN_COVER => 'coverPath',
            self::KEY_ORGAN_THUMBNAIL => 'thumbnailPath',
            default => null,
        };
    }

    /**
     * Look a logged row back up by its stable key and id, through the matching (typed) repository.
     */
    private function findByKey(
        string $key,
        int $id,
    ): ?object {
        return match ($key) {
            self::KEY_PHOTO => $this->photoRepository->find($id),
            self::KEY_ALBUM_COVER => $this->albumRepository->find($id),
            self::KEY_COMPANY_LOGO => $this->companyRevisionRepository->find($id),
            self::KEY_COMPANY_BANNER => $this->companyBannerPackageRepository->find($id),
            self::KEY_ORGAN_COVER, self::KEY_ORGAN_THUMBNAIL => $this->organInformationRepository->find($id),
            default => null,
        };
    }

    /**
     * The integer identifier of a managed entity, read through the ORM metadata (so no shared id interface is needed).
     */
    private function entityId(object $entity): int
    {
        $identifiers = $this->entityManager
            ->getClassMetadata($entity::class)
            ->getIdentifierValues($entity);

        $id = $identifiers['id'] ?? null;
        if (!is_int($id)) {
            throw new RuntimeException(sprintf('Expected an integer id on "%s".', $entity::class));
        }

        return $id;
    }

    /**
     * Hardlink one file, creating the destination directory as needed. Returns which of the three outcomes occurred.
     */
    private function linkFile(
        string $source,
        string $destination,
    ): string {
        if (!is_file($source)) {
            return self::LINK_MISSING_SOURCE;
        }

        if (file_exists($destination)) {
            return self::LINK_SKIPPED;
        }

        $directory = dirname($destination);
        if (
            !is_dir($directory)
            && !mkdir(
                $directory,
                0o775,
                true,
            )
            && !is_dir($directory)
        ) {
            throw new RuntimeException(sprintf('Could not create the destination directory "%s".', $directory));
        }

        if (
            !link(
                $source,
                $destination,
            )
        ) {
            throw new RuntimeException(sprintf('Could not hardlink "%s" to "%s".', $source, $destination));
        }

        return self::LINK_LINKED;
    }

    /**
     * Write the rollback log for a `--paths` run and return its absolute path.
     *
     * Build the path for a new rollback log (JSON Lines, one entry per line so it can be appended to durably) and
     * ensure its directory exists. The file itself is created on the first {@see appendLog()}.
     */
    private function newLogFile(): string
    {
        $directory = $this->logDirectory();
        if (
            !is_dir($directory)
            && !mkdir(
                $directory,
                0o775,
                true,
            )
            && !is_dir($directory)
        ) {
            throw new RuntimeException(sprintf('Could not create the log directory "%s".', $directory));
        }

        return sprintf(
            '%s/paths-%s.jsonl',
            $directory,
            date('Ymd-His'),
        );
    }

    /**
     * Append a batch of rollback entries to the log file, one JSON object per line.
     *
     * @param list<LogEntry> $entries
     */
    private function appendLog(
        string $file,
        array $entries,
    ): void {
        if ([] === $entries) {
            return;
        }

        $lines = '';
        foreach ($entries as $entry) {
            $lines .= json_encode(
                $entry,
                JSON_THROW_ON_ERROR,
            ) . "\n";
        }

        if (
            false === file_put_contents(
                $file,
                $lines,
                FILE_APPEND | LOCK_EX,
            )
        ) {
            throw new RuntimeException(sprintf('Could not write the rollback log to "%s".', $file));
        }
    }

    /**
     * Read and validate a rollback log, returning only well-formed entries with a known key. Malformed lines are
     * skipped rather than aborting the restore.
     *
     * @return list<LogEntry>
     */
    private function readLog(string $file): array
    {
        $contents = file_get_contents($file);
        if (false === $contents) {
            throw new RuntimeException(sprintf('Could not read the rollback log "%s".', $file));
        }

        $entries = [];
        foreach (
            explode(
                "\n",
                $contents,
            ) as $line
        ) {
            if ('' === trim($line)) {
                continue;
            }

            try {
                $decoded = json_decode(
                    $line,
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException $e) {
                throw new RuntimeException(
                    sprintf(
                        'The rollback log "%s" has an invalid line: %s',
                        $file,
                        $e->getMessage(),
                    ),
                    previous: $e,
                );
            }

            if (
                !is_array($decoded)
                || !isset($decoded['key'], $decoded['id'], $decoded['old'], $decoded['new'])
                || !is_string($decoded['key'])
            ) {
                continue;
            }

            $entries[] = [
                'key' => $decoded['key'],
                'id' => intval($decoded['id']),
                'old' => strval($decoded['old']),
                'new' => strval($decoded['new']),
            ];
        }

        return $entries;
    }

    /**
     * Resolve which rollback log to restore from: the explicit --log value, or the most recent log otherwise.
     */
    private function resolveLogFile(?string $logOption): ?string
    {
        if (null !== $logOption) {
            $path = str_starts_with(
                $logOption,
                '/',
            )
                ? $logOption
                : $this->projectDir . '/' . $logOption;

            return is_file($path)
                ? $path
                : null;
        }

        $matches = glob($this->logDirectory() . '/paths-*.jsonl');
        if (
            false === $matches
            || [] === $matches
        ) {
            return null;
        }

        // The filenames carry a sortable timestamp, so the last entry is the most recent run.
        sort($matches);

        return $matches[count($matches) - 1];
    }

    /**
     * Print a small sample of the legacy-to-new mappings, so a dry run makes the transformation concrete.
     *
     * @param list<array{0: string, 1: string}> $sample
     */
    private function reportSample(
        SymfonyStyle $ui,
        array $sample,
    ): void {
        if ([] === $sample) {
            return;
        }

        $ui->table(
            [
                'Legacy path',
                'New path',
            ],
            $sample,
        );
    }

    /**
     * Ask for confirmation before a destructive run; a dry run and non-interactive runs (cron/CI) proceed silently.
     */
    private function confirmDestructive(
        SymfonyStyle $ui,
        InputInterface $input,
        bool $dryRun,
        string $action,
    ): bool {
        if ($dryRun) {
            return true;
        }

        return $ui->confirm(
            sprintf(
                'This will %s. Continue?',
                $action,
            ),
            !$input->isInteractive(),
        );
    }

    /**
     * Parse and validate the --batch-size option; null signals an invalid value (already reported to the user).
     */
    private function batchSize(
        SymfonyStyle $ui,
        InputInterface $input,
    ): ?int {
        $value = intval($input->getOption('batch-size'));

        if ($value < 1) {
            $ui->error('The --batch-size must be a positive integer.');

            return null;
        }

        return $value;
    }

    private function stringOption(
        InputInterface $input,
        string $name,
    ): ?string {
        $value = $input->getOption($name);

        return is_string($value)
            ? $value
            : null;
    }

    private function legacyRoot(): string
    {
        return $this->projectDir . '/public/data';
    }

    private function newRoot(): string
    {
        return $this->projectDir . '/data';
    }

    private function logDirectory(): string
    {
        return $this->projectDir . '/var/storage-migration';
    }
}
