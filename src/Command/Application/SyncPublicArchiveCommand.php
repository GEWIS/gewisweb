<?php

declare(strict_types=1);

namespace App\Command\Application;

use Override;
use phpseclib3\Net\SFTP;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;

use function rtrim;
use function sprintf;

#[AsCommand(
    name: 'app:public-archive:sync',
    description: 'Mirror the remote public archive over SFTP into data/public-archive/.',
)]
#[AsCronTask(expression: '0 * * * *')]
final class SyncPublicArchiveCommand extends Command
{
    use LockableTrait;

    private const string REMOTE_ROOT = '/datas/Public Archive';
    private const int CONNECT_TIMEOUT_SECONDS = 30;

    /**
     * Unfortunately, phpseclib defines SFTP file types as global constants, but they do this at runtime. So to please
     * our static analysers, we mirror the small subset we care about as class constants and compare against the numeric
     * values phpseclib places in rawlist()['type']
     */
    private const int SFTP_TYPE_REGULAR = 1;
    private const int SFTP_TYPE_DIRECTORY = 2;

    public function __construct(
        #[Autowire('%env(SSH_REMOTE)%')]
        private readonly string $sshRemote,
        #[Autowire('%env(SSH_USERNAME)%')]
        private readonly string $sshUsername,
        #[Autowire('%env(SSH_PASSWORD)%')]
        private readonly string $sshPassword,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
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
            return $this->doSync($io);
        } finally {
            $this->release();
        }
    }

    private function doSync(SymfonyStyle $io): int
    {
        $localRoot = $this->projectDir . '/data/public-archive';
        $filesystem = new Filesystem();
        $filesystem->mkdir($localRoot);

        $sftp = new SFTP($this->sshRemote);
        $sftp->setTimeout(self::CONNECT_TIMEOUT_SECONDS);

        // TODO: pin the remote host key via $sftp->getServerPublicHostKey() once a known-good fingerprint is recorded.
        // Matches the StrictHostKeyChecking=no behaviour from the script for now.
        if (
            !$sftp->login(
                $this->sshUsername,
                $this->sshPassword,
            )
        ) {
            $io->error(sprintf('Could not authenticate against %s as %s.', $this->sshRemote, $this->sshUsername));
            $this->logger->error(
                'Public archive sync failed: SFTP login rejected.',
                [
                    'remote' => $this->sshRemote,
                    'username' => $this->sshUsername,
                ],
            );

            return Command::FAILURE;
        }

        try {
            $this->clearLocalRoot(
                $filesystem,
                $localRoot,
            );

            $stats = [
                'files' => 0,
                'bytes' => 0,
            ];
            $this->mirror(
                $sftp,
                self::REMOTE_ROOT,
                $localRoot,
                $stats,
            );
        } catch (Throwable $e) {
            $io->error('Public archive sync failed: ' . $e->getMessage());
            $this->logger->error(
                'Public archive sync failed.',
                ['exception' => $e],
            );

            return Command::FAILURE;
        } finally {
            $sftp->disconnect();
        }

        $summary = sprintf(
            'Mirrored %d file(s) (%d bytes) from %s.',
            $stats['files'],
            $stats['bytes'],
            self::REMOTE_ROOT,
        );
        $io->success($summary);
        $this->logger->info($summary);

        return Command::SUCCESS;
    }

    private function clearLocalRoot(
        Filesystem $filesystem,
        string $localRoot,
    ): void {
        if (!$filesystem->exists($localRoot)) {
            return;
        }

        // Wipe only the children, preserving the directory and its tracked .gitignore (which keeps the archive contents
        // out of git).
        $children = new Finder()
            ->in($localRoot)
            ->depth(0)
            ->ignoreDotFiles(false)
            ->notName('.gitignore');
        $filesystem->remove($children);
    }

    /**
     * @param array{files: int, bytes: int} $stats
     */
    private function mirror(
        SFTP $sftp,
        string $remoteDir,
        string $localDir,
        array &$stats,
    ): void {
        $entries = $sftp->rawlist($remoteDir);

        if (false === $entries) {
            throw new RuntimeException(sprintf('Could not list remote directory: %s', $remoteDir));
        }

        foreach ($entries as $name => $attrs) {
            if (
                '.' === $name
                || '..' === $name
            ) {
                continue;
            }

            $remotePath = rtrim(
                $remoteDir,
                '/',
            ) . '/' . $name;
            $localPath = $localDir . '/' . $name;
            $type = $attrs['type'] ?? null;

            if (self::SFTP_TYPE_DIRECTORY === $type) {
                new Filesystem()->mkdir($localPath);
                $this->mirror(
                    $sftp,
                    $remotePath,
                    $localPath,
                    $stats,
                );

                continue;
            }

            if (self::SFTP_TYPE_REGULAR !== $type) {
                $this->logger->debug(
                    'Skipping non-regular SFTP entry.',
                    [
                        'path' => $remotePath,
                        'type' => $type,
                    ],
                );

                continue;
            }

            if (
                false === $sftp->get(
                    $remotePath,
                    $localPath,
                )
            ) {
                throw new RuntimeException(sprintf('Failed to download remote file: %s', $remotePath));
            }

            $stats['files']++;
            $stats['bytes'] += (int) ($attrs['size'] ?? 0);
        }
    }
}
