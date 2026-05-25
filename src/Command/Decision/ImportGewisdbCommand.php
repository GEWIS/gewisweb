<?php

declare(strict_types=1);

namespace App\Command\Decision;

use App\Entity\Decision\Member;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

use function array_fill;
use function array_keys;
use function array_map;
use function array_walk_recursive;
use function count;
use function implode;
use function sprintf;
use function str_repeat;
use function strval;

#[AsCommand(
    name: 'app:decision:import-gewisdb',
    description: 'Sync the GEWIS Report Database (GEWISDB) into the website database.',
)]
#[AsCronTask(expression: '28,58 * * * *')]
final class ImportGewisdbCommand extends Command
{
    use LockableTrait;

    /**
     * Tables to copy from GEWISDB (PostgreSQL) into GEWISWEB (MariaDB). The schemas are kept compatible on both sides
     * so a column-by-column copy is sufficient.
     */
    private const array TABLES = [
        'Address',
        'BoardMember',
        'Decision',
        'MailingList',
        'MailingListMember',
        'Meeting',
        'Member',
        'Keyholder',
        'Organ',
        'OrganMember',
        'organs_subdecisions',
        'SubDecision',
    ];

    private const int BATCH_SIZE = 256;

    public function __construct(
        #[Autowire(service: 'gewisdb.client')]
        private readonly HttpClientInterface $gewisdbClient,
        private readonly Connection $connection,
        #[Autowire(service: 'doctrine.dbal.gewisdb_connection')]
        private readonly Connection $gewisdbConnection,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle(
            $input,
            $output,
        );

        if (!$this->lock()) {
            $io->writeln('Another instance of this command is already running, skipping.');

            return Command::SUCCESS;
        }

        try {
            if (!$this->isRemoteHealthy($io)) {
                return Command::FAILURE;
            }

            $io->writeln('Commencing sync with GEWISDB...');
            $this->logger->info('Commencing sync with GEWISDB.');

            try {
                $this->connection->executeStatement('SET foreign_key_checks = 0');

                $this->connection->transactional(function (Connection $conn) use ($io): void {
                    $pks = $this->fetchPrimaryKeysByTable($conn);

                    foreach (self::TABLES as $table) {
                        $io->writeln(sprintf('Syncing table "%s"...', $table));

                        $upserted = $this->upsertRows(
                            $conn,
                            $table,
                        );
                        $io->writeln(sprintf(
                            'Inserted or updated %d row(s) in "%s" (updates are counted twice).',
                            $upserted,
                            $table,
                        ));

                        $primaryKeys = $pks[$table] ?? [];
                        if ([] === $primaryKeys) {
                            continue;
                        }

                        $deleted = $this->deleteOrphans(
                            $conn,
                            $table,
                            $primaryKeys,
                        );
                        $io->writeln(sprintf('Deleted %d row(s) from "%s".', $deleted, $table));
                    }
                });

                // The sync writes via raw DBAL, so Doctrine cannot invalidate the second-level cache automatically.
                // Evict the `Member` region here so consumers see the fresh data immediately instead of after TTL.
                $this->entityManager->getCache()?->evictEntityRegion(Member::class);

                $io->success('Sync with GEWISDB completed.');
                $this->logger->info('Sync with GEWISDB completed.');

                return Command::SUCCESS;
            } catch (Throwable $e) {
                $io->error('Sync with GEWISDB failed: ' . $e->getMessage());
                $this->logger->error(
                    'Sync with GEWISDB failed.',
                    ['exception' => $e],
                );

                return Command::FAILURE;
            } finally {
                $this->connection->executeStatement('SET foreign_key_checks = 1');
            }
        } finally {
            $this->release();
        }
    }

    private function isRemoteHealthy(SymfonyStyle $io): bool
    {
        try {
            $response = $this->gewisdbClient->request(
                'GET',
                '/health',
            );
            $statusCode = $response->getStatusCode();
        } catch (HttpExceptionInterface $e) {
            $io->error('API: no sync, request failed: ' . $e->getMessage());
            $this->logger->error(
                'GEWISDB health check failed.',
                ['exception' => $e],
            );

            return false;
        }

        if (
            200 !== $statusCode
            && 403 !== $statusCode
        ) {
            $io->error(sprintf('API: no sync, unexpected response (HTTP %d).', $statusCode));
            $this->logger->warning(
                'GEWISDB health check returned unexpected status.',
                ['status' => $statusCode],
            );

            return false;
        }

        try {
            $health = $response->toArray(throw: false);
        } catch (HttpExceptionInterface $e) {
            $io->error('API: no sync, invalid JSON returned: ' . $e->getMessage());
            $this->logger->error(
                'GEWISDB health check returned invalid JSON.',
                ['exception' => $e],
            );

            return false;
        }

        if (
            true !== ($health['healthy'] ?? false)
            || true === ($health['sync_paused'] ?? false)
        ) {
            $io->warning('API: no sync, sync is paused or API is not healthy.');
            $this->logger->info(
                'GEWISDB sync skipped: paused or unhealthy.',
                ['health' => $health],
            );

            return false;
        }

        $io->writeln('API: sync, healthy and syncs are allowed.');

        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    private function fetchPrimaryKeysByTable(Connection $conn): array
    {
        $rows = $conn->fetchAllAssociative(
            <<<'SQL'
                SELECT TABLE_NAME, COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = :schema
                  AND CONSTRAINT_NAME = 'PRIMARY'
                SQL,
            ['schema' => $conn->getDatabase()],
        );

        $pks = [];
        foreach ($rows as $row) {
            $pks[strval($row['TABLE_NAME'])][] = strval($row['COLUMN_NAME']);
        }

        return $pks;
    }

    /**
     * Reads every row of $table from the GEWISDB connection and batch-inserts it on the local connection. Mirrors the
     * 256-row batching from the legacy importdb.php.
     */
    private function upsertRows(
        Connection $conn,
        string $table,
    ): int {
        $sourceQuery = sprintf(
            'SELECT * FROM %s',
            $this->quoteSourceIdentifier($table),
        );
        $result = $this->gewisdbConnection->executeQuery($sourceQuery);

        $upserted = 0;
        $batch = [];

        while (false !== ($row = $result->fetchAssociative())) {
            $batch[] = $row;

            if (count($batch) < self::BATCH_SIZE) {
                continue;
            }

            $upserted += $this->flushBatch(
                $conn,
                $table,
                $batch,
            );
            $batch = [];
        }

        if ([] !== $batch) {
            $upserted += $this->flushBatch(
                $conn,
                $table,
                $batch,
            );
        }

        return $upserted;
    }

    /**
     * @param non-empty-list<array<string, mixed>> $batch
     */
    private function flushBatch(
        Connection $conn,
        string $table,
        array $batch,
    ): int {
        $columns = array_keys($batch[0]);
        $columnList = implode(
            ', ',
            array_map(
                fn (string $c): string => $this->quoteTargetIdentifier($c),
                $columns,
            ),
        );

        $placeholderGroups = [];
        $params = [];
        foreach ($batch as $i => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $col) {
                $param = sprintf(
                    '%s_%d',
                    $col,
                    $i,
                );
                $rowPlaceholders[] = ':' . $param;
                $params[$param] = $row[$col] ?? null;
            }

            $placeholderGroups[] = '(' . implode(
                ', ',
                $rowPlaceholders,
            ) . ')';
        }

        $updates = implode(
            ', ',
            array_map(
                fn (string $c): string => sprintf(
                    '%1$s=VALUES(%1$s)',
                    $this->quoteTargetIdentifier($c),
                ),
                $columns,
            ),
        );

        $sql = sprintf(
            'INSERT IGNORE INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
            $this->quoteTargetIdentifier($table),
            $columnList,
            implode(
                ', ',
                $placeholderGroups,
            ),
            $updates,
        );

        return $conn->executeStatement(
            $sql,
            $params,
        );
    }

    /**
     * Removes rows from the local table whose primary keys no longer exist in the GEWISDB source.
     *
     * @param non-empty-list<string> $primaryKeys
     */
    private function deleteOrphans(
        Connection $conn,
        string $table,
        array $primaryKeys,
    ): int {
        $idQuery = sprintf(
            'SELECT %s FROM %s',
            implode(
                ', ',
                array_map(
                    fn (string $c): string => $this->quoteSourceIdentifier($c),
                    $primaryKeys,
                ),
            ),
            $this->quoteSourceIdentifier($table),
        );
        $ids = $this->gewisdbConnection->fetchAllAssociative($idQuery);

        // If the source table is empty, the legacy script substitutes a single all-NULL tuple so the NOT IN clause
        // keeps working. Replicating that behaviour keeps the delete-not-in-set SQL valid (an empty IN-list is illegal)
        // while never matching real rows.
        if ([] === $ids) {
            $ids = [
                array_fill(
                    0,
                    count($primaryKeys),
                    null,
                ),
            ];
        }

        $idsFlat = [];
        array_walk_recursive(
            $ids,
            static function (mixed $value) use (&$idsFlat): void {
                $idsFlat[] = $value;
            },
        );

        $tuplePlaceholder = '(' . str_repeat(
            '?,',
            count($primaryKeys) - 1,
        ) . '?)';
        $pkList = '(' . implode(
            ', ',
            array_map(
                fn (string $c): string => $this->quoteTargetIdentifier($c),
                $primaryKeys,
            ),
        ) . ')';

        $sql = sprintf(
            'DELETE FROM %s WHERE %s NOT IN (%s)',
            $this->quoteTargetIdentifier($table),
            $pkList,
            implode(
                ', ',
                array_fill(
                    0,
                    count($ids),
                    $tuplePlaceholder,
                ),
            ),
        );

        return $conn->executeStatement(
            $sql,
            $idsFlat,
        );
    }

    private function quoteSourceIdentifier(string $identifier): string
    {
        return $this->gewisdbConnection->quoteSingleIdentifier($identifier);
    }

    private function quoteTargetIdentifier(string $identifier): string
    {
        return $this->connection->quoteSingleIdentifier($identifier);
    }
}
