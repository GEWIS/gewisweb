<?php

declare(strict_types=1);

namespace App\Command\Frontpage;

use App\Repository\Frontpage\PageRepository;
use App\Service\Frontpage\PageContentSanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

/**
 * Puts the pages that are already stored through the sanitizer, once.
 *
 * Saving a page sanitises it, so from now on nothing gets in that should not. What was written before that was true
 * has never been checked, which is what this is for. It is deliberately not scheduled: a page is only ever written by
 * somebody the board trusts, and running it over and over would be answering a question that has been settled.
 */
#[AsCommand(
    name: 'app:page:sanitise',
    description: 'Put the custom pages that are already stored through the sanitizer.',
)]
final class SanitisePagesCommand extends Command
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly PageContentSanitizer $sanitizer,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Report what would change without writing anything.',
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

        $dryRun = true === $input->getOption('dry-run');
        $changed = 0;

        foreach ($this->pageRepository->findAll() as $page) {
            $content = $page->getContent();
            $english = $this->sanitizer->sanitize($content->getValueEN());
            $dutch = $this->sanitizer->sanitize($content->getValueNL());

            if (
                $english === $content->getValueEN()
                && $dutch === $content->getValueNL()
            ) {
                continue;
            }

            ++$changed;
            $io->writeln(sprintf(
                'Page #%d contains markup that is not allowed.',
                $page->getId() ?? 0,
            ));

            if ($dryRun) {
                continue;
            }

            $content->updateValues(
                $english,
                $dutch,
            );
        }

        if ($dryRun) {
            $io->note(sprintf(
                '%d page(s) would change. Nothing was written.',
                $changed,
            ));

            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success(sprintf(
            '%d page(s) were rewritten.',
            $changed,
        ));

        return Command::SUCCESS;
    }
}
