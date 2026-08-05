<?php

declare(strict_types=1);

namespace App\Command\Education;

use App\Entity\Education\Enums\DocumentFlattenStatus;
use App\Message\Education\FlattenCourseDocumentMessage;
use App\Repository\Education\CourseDocumentRepository;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

use function count;
use function sprintf;

/**
 * The archive predates the pipeline: what is in it was uploaded when a download meant handing over the file itself, so
 * none of it is downloadable until this has run. It is also the way back after a batch fails, since a failed document
 * stays failed until something asks for it again. `--limit` paces it: the archive is thousands of documents at roughly
 * a second each.
 */
#[AsCommand(
    name: 'app:education:flatten-documents',
    description: 'Queue course documents that have not been rasterized yet.',
)]
final class FlattenDocumentsCommand extends Command
{
    public function __construct(
        private readonly CourseDocumentRepository $documentRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'How many documents to queue at most.',
            )
            ->addOption(
                'retry-failed',
                null,
                InputOption::VALUE_NONE,
                'Also queue documents that failed, rather than only ones never tried.',
            );
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

        $limit = $input->getOption('limit');
        $statuses = true === $input->getOption('retry-failed')
            ? [
                DocumentFlattenStatus::Pending,
                DocumentFlattenStatus::Failed,
            ]
            : [DocumentFlattenStatus::Pending];

        $documents = $this->documentRepository->findByFlattenStatus(
            $statuses,
            null !== $limit ? (int) $limit : null,
        );

        foreach ($documents as $document) {
            $this->messageBus->dispatch(new FlattenCourseDocumentMessage($document->getId() ?? 0));
        }

        $queued = count($documents);
        $io->success(sprintf('Queued %d document%s.', $queued, 1 !== $queued ? 's' : ''));

        return Command::SUCCESS;
    }
}
