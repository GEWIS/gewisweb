<?php

declare(strict_types=1);

namespace App\Command\Decision;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Decision\LegacyMeetingDocument;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingDocumentVersion;
use App\Entity\Decision\MeetingMinutes;
use App\Entity\Decision\MeetingMinutesVersion;
use App\Entity\Decision\MeetingPoint;
use App\Entity\Decision\MeetingReferenceSelection;
use App\Entity\Decision\ReferenceDocument;
use App\Entity\Decision\ReferenceDocumentVersion;
use App\Repository\Decision\LegacyMeetingDocumentRepository;
use App\Repository\Decision\LegacyMeetingMinutesRepository;
use App\Service\Application\FileStorage;
use App\Service\Application\FileStorageException;
use App\Service\Decision\LegacyDocumentNameParser;
use App\Service\Decision\ParsedLegacyName;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function array_key_exists;
use function array_keys;
use function count;
use function intval;
use function is_file;
use function ksort;
use function sprintf;
use function str_starts_with;
use function strval;
use function usort;

/**
 * One-shot migration of the legacy flat meeting documents (preserved as `LegacyMeetingDocument` and
 * `LegacyMeetingMinutes` by the schema migration) into the agenda-point/version model. Names are interpreted by
 * {@see LegacyDocumentNameParser}: parsed point prefixes become agenda points, rows differing only in their version
 * or date suffix collapse into one document with multiple versions, and known recurring documents move into the
 * reference library with a per-meeting pinned version. Anything unparseable stays a meeting-level document under its
 * original name.
 *
 * `--dry-run` computes and reports the same migration without writing to the database or the file storage. Files are
 * read from the legacy content-addressed layout, which the storage migration never covered for meeting documents.
 */
#[AsCommand(name: 'app:decision:migrate-legacy-meeting-documents')]
class MigrateLegacyMeetingDocumentsCommand extends Command
{
    private const array REFERENCE_NAMES = [
        'eternal-memorandum-and-decision-list' => 'Eternal Memorandum and Decision List',
        'eternal-memorandum' => 'Eternal Memorandum',
        'eternal-decision-list' => 'Eternal Decision List',
        'scenarios-and-procedures' => 'Scenarios and Procedures',
        'summaries-of-old-gmms' => 'Summaries of old GMMs',
        'financial-definition-list' => 'Financial Definition List',
        'translation-template-decision-list' => 'Translation Template Decision List',
    ];

    private bool $dryRun = false;
    private string $sourceDir = '';

    /** @var list<string> */
    private array $missingFiles = [];

    /** @var list<string> */
    private array $rejectedFiles = [];

    /** @var list<string> */
    private array $unparsedNames = [];

    /** @var array<string, int> */
    private array $counters = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LegacyMeetingDocumentRepository $legacyDocuments,
        private readonly LegacyMeetingMinutesRepository $legacyMinutes,
        private readonly FileStorage $fileStorage,
        private readonly LegacyDocumentNameParser $parser,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
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
            'Compute and report the migration without writing anything',
        );
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Run even when migrated documents already exist',
        );
        $this->addOption(
            'source-dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Directory holding the legacy content-addressed files',
            'public/data',
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

        $this->dryRun = true === $input->getOption('dry-run');
        $sourceDir = strval($input->getOption('source-dir'));
        $this->sourceDir = str_starts_with(
            $sourceDir,
            '/',
        )
            ? $sourceDir
            : $this->projectDir . '/' . $sourceDir;

        $existing = $this->entityManager->getRepository(MeetingDocument::class)->count();
        if (
            $existing > 0
            && true !== $input->getOption('force')
        ) {
            $io->error(sprintf(
                'There are already %d migrated meeting documents; pass --force to migrate anyway.',
                $existing,
            ));

            return Command::FAILURE;
        }

        $rows = $this->legacyDocuments->findAllOrderedById();

        $referenceRows = [];
        $documentRows = [];
        foreach ($rows as $row) {
            $this->count('legacy document rows read');

            $parsed = $this->parser->parse($row->getName());

            if (null !== $parsed->referenceKey) {
                $referenceRows[$parsed->referenceKey][] = [
                    $row,
                    $parsed,
                ];
                continue;
            }

            if (
                null === $parsed->pointNumber
                && null === $parsed->versionLabel
                && null === $parsed->versionDate
            ) {
                $this->unparsedNames[] = $row->getName();
            }

            $meeting = $row->getMeeting();
            $documentRows[$meeting->getType()->value][$meeting->getNumber()][] = [
                $row,
                $parsed,
            ];
        }

        $this->migrateReferences($referenceRows);
        $this->migrateDocuments($documentRows);
        $this->migrateMinutes();

        if (!$this->dryRun) {
            $this->entityManager->flush();
        }

        $this->report($io);

        return Command::SUCCESS;
    }

    /**
     * Every meeting that shipped a recurring document gets a selection pinned to the exact version it shipped. The
     * legacy paths are content addressed, so identical paths are identical files and become one library version,
     * attributed to the first meeting that shipped them.
     *
     * @param array<string, list<array{LegacyMeetingDocument, ParsedLegacyName}>> $referenceRows
     */
    private function migrateReferences(array $referenceRows): void
    {
        foreach ($referenceRows as $key => $rows) {
            usort(
                $rows,
                static fn (array $a, array $b): int => [
                    $a[0]->getMeeting()->getDate(),
                    $a[0]->getId(),
                ]
                    <=> [
                        $b[0]->getMeeting()->getDate(),
                        $b[0]->getId(),
                    ],
            );

            $storedByPath = [];
            $survivors = 0;
            foreach ($rows as [$row]) {
                $legacyPath = $row->getPath();
                if (
                    array_key_exists(
                        $legacyPath,
                        $storedByPath,
                    )
                ) {
                    continue;
                }

                $storedByPath[$legacyPath] = $this->store(
                    $legacyPath,
                    StorageNamespace::ReferenceDocument,
                    $row->getName(),
                );
                if (null === $storedByPath[$legacyPath]) {
                    continue;
                }

                $survivors++;
            }

            if (0 === $survivors) {
                $this->count('reference documents without surviving files');
                continue;
            }

            $document = new ReferenceDocument();
            $document->setName(self::REFERENCE_NAMES[$key]);
            $this->persist($document);
            $this->count('reference documents');

            $versionByPath = [];
            $selectionByMeeting = [];
            $sequence = 0;
            foreach ($rows as [$row, $parsed]) {
                $storedPath = $storedByPath[$row->getPath()];
                if (null === $storedPath) {
                    continue;
                }

                if (!isset($versionByPath[$row->getPath()])) {
                    $sequence++;
                    $version = new ReferenceDocumentVersion();
                    $version->setReferenceDocument($document);
                    $version->setVersionLabel($parsed->versionLabel ?? 'v' . $sequence);
                    $version->setPath($storedPath);
                    $version->setUploadedAt($this->uploadedAt(
                        $parsed,
                        $row->getCreatedAt(),
                    ));
                    $this->persist($version);
                    $this->count('reference document versions');

                    $versionByPath[$row->getPath()] = $version;
                }

                // A meeting can have shipped the document twice; the newest shipment wins the pin.
                $meeting = $row->getMeeting();
                $meetingKey = $meeting->getType()->value . '|' . $meeting->getNumber();
                if (isset($selectionByMeeting[$meetingKey])) {
                    $selectionByMeeting[$meetingKey]->setPinnedVersion($versionByPath[$row->getPath()]);
                    continue;
                }

                $selection = new MeetingReferenceSelection();
                $selection->setMeeting($meeting);
                $selection->setReferenceDocument($document);
                $selection->setPinnedVersion($versionByPath[$row->getPath()]);
                $this->persist($selection);
                $this->count('reference selections');

                $selectionByMeeting[$meetingKey] = $selection;
            }
        }
    }

    /**
     * @param array<string, array<int, list<array{LegacyMeetingDocument, ParsedLegacyName}>>> $documentRows
     */
    private function migrateDocuments(array $documentRows): void
    {
        foreach ($documentRows as $byNumber) {
            foreach ($byNumber as $rows) {
                $meeting = $rows[0][0]->getMeeting();

                // The versions of one document share an agenda point and a normalised base name.
                $groups = [];
                foreach ($rows as $entry) {
                    $groups[($entry[1]->pointNumber ?? '~') . '|' . $entry[1]->groupKey][] = $entry;
                }

                $survivingGroups = [];
                foreach ($groups as $entries) {
                    usort(
                        $entries,
                        static fn (array $a, array $b): int => $a[0]->getId() <=> $b[0]->getId(),
                    );

                    $stored = [];
                    foreach ($entries as [$row, $parsed]) {
                        $storedPath = $this->store(
                            $row->getPath(),
                            StorageNamespace::MeetingDocument,
                            $row->getName(),
                            $meeting->getStorageScope(),
                        );
                        if (null === $storedPath) {
                            continue;
                        }

                        $stored[] = [
                            $row,
                            $parsed,
                            $storedPath,
                        ];
                    }

                    if ([] === $stored) {
                        $this->count('documents without surviving files');
                        continue;
                    }

                    $survivingGroups[] = $stored;
                }

                // Points only exist for documents that survived; numeric string keys collapse to
                // integers, so a plain key sort is numeric. The board can retitle and reorder later.
                $pointNumbers = [];
                foreach ($survivingGroups as $stored) {
                    $pointNumber = $stored[0][1]->pointNumber;
                    if (null === $pointNumber) {
                        continue;
                    }

                    $pointNumbers[intval($pointNumber)] = true;
                }

                ksort($pointNumbers);

                $points = [];
                $position = 0;
                foreach (array_keys($pointNumbers) as $pointNumber) {
                    $point = new MeetingPoint();
                    $point->setMeeting($meeting);
                    $point->setNumber(strval($pointNumber));
                    $point->setTitle('');
                    $point->setDisplayPosition($position);
                    $position++;

                    $this->persist($point);
                    $this->count('agenda points');
                    $points[$pointNumber] = $point;
                }

                $meetingLevelPosition = 0;
                foreach ($survivingGroups as $stored) {
                    $newest = $stored[count($stored) - 1];
                    $pointNumber = $newest[1]->pointNumber;

                    $document = new MeetingDocument();
                    $document->setMeeting($meeting);
                    $document->setPoint(null === $pointNumber ? null : $points[intval($pointNumber)]);
                    $document->setName($newest[1]->baseName);
                    $document->setDisplayPosition(
                        null === $pointNumber
                            ? $meetingLevelPosition
                            : $newest[0]->getDisplayPosition(),
                    );
                    $this->persist($document);
                    $this->count('documents');

                    if (null === $pointNumber) {
                        $meetingLevelPosition++;
                    }

                    $sequence = 0;
                    foreach ($stored as [$row, $parsed, $storedPath]) {
                        $sequence++;
                        $version = new MeetingDocumentVersion();
                        $version->setDocument($document);
                        $version->setVersionLabel($parsed->versionLabel ?? 'v' . $sequence);
                        $version->setPath($storedPath);
                        $version->setUploadedAt($this->uploadedAt(
                            $parsed,
                            $row->getCreatedAt(),
                        ));
                        $this->persist($version);
                        $this->count('document versions');
                    }
                }
            }
        }
    }

    private function migrateMinutes(): void
    {
        $rows = $this->legacyMinutes->findAll();

        foreach ($rows as $row) {
            $meeting = $row->getMeeting();
            $described = sprintf(
                'Minutes %s %d',
                $meeting->getType()->value,
                $meeting->getNumber(),
            );

            $storedPath = $this->store(
                $row->getPath(),
                StorageNamespace::MeetingMinutes,
                $described,
                $meeting->getStorageScope(),
            );
            if (null === $storedPath) {
                continue;
            }

            $minutes = new MeetingMinutes();
            $minutes->setMeeting($meeting);
            $this->persist($minutes);

            $version = new MeetingMinutesVersion();
            $version->setMinutes($minutes);
            $version->setVersionLabel('v1.0');
            $version->setPath($storedPath);
            $version->setUploadedAt(
                intval($row->getUpdatedAt()->format('Y')) >= 2000
                    ? DateTime::createFromInterface($row->getUpdatedAt())
                    : null,
            );
            $this->persist($version);
            $this->count('minutes');
        }
    }

    private function store(
        string $legacyPath,
        StorageNamespace $namespace,
        string $describedAs,
        ?string $scope = null,
    ): ?string {
        $absolutePath = $this->sourceDir . '/' . $legacyPath;

        if (!is_file($absolutePath)) {
            $this->missingFiles[] = sprintf(
                '%s (%s)',
                $legacyPath,
                $describedAs,
            );

            return null;
        }

        if ($this->dryRun) {
            return 'dry-run/' . $legacyPath;
        }

        try {
            return $this->fileStorage->store(
                $namespace,
                $absolutePath,
                $scope,
            )->path;
        } catch (FileStorageException $e) {
            $this->rejectedFiles[] = sprintf(
                '%s (%s): %s',
                $legacyPath,
                $describedAs,
                $e->getMessage(),
            );

            return null;
        }
    }

    /**
     * The oldest legacy imports carry a placeholder timestamp far in the past, which really means "unknown".
     */
    private function uploadedAt(
        ParsedLegacyName $parsed,
        DateTime $createdAt,
    ): ?DateTime {
        if (null !== $parsed->versionDate) {
            return DateTime::createFromImmutable($parsed->versionDate);
        }

        if (intval($createdAt->format('Y')) >= 2000) {
            return DateTime::createFromInterface($createdAt);
        }

        return null;
    }

    private function persist(object $entity): void
    {
        if ($this->dryRun) {
            return;
        }

        $this->entityManager->persist($entity);
    }

    private function count(string $key): void
    {
        $this->counters[$key] = ($this->counters[$key] ?? 0) + 1;
    }

    private function report(SymfonyStyle $io): void
    {
        $io->title($this->dryRun ? 'Legacy meeting document migration (dry run)' : 'Legacy meeting document migration');

        $rows = [];
        foreach ($this->counters as $key => $value) {
            $rows[] = [
                $key,
                $value,
            ];
        }

        $rows[] = [
            'missing files',
            count($this->missingFiles),
        ];
        $rows[] = [
            'rejected files',
            count($this->rejectedFiles),
        ];
        $rows[] = [
            'names kept verbatim as meeting-level documents',
            count($this->unparsedNames),
        ];

        // Not $io->table(): that grabs a console section, which the test harness output does not support.
        new Table($io)
            ->setHeaders(['What', 'Count'])
            ->setRows($rows)
            ->render();

        if ($io->isVerbose()) {
            $this->listing(
                $io,
                'Names kept verbatim',
                $this->unparsedNames,
            );
            $this->listing(
                $io,
                'Missing files',
                $this->missingFiles,
            );
            $this->listing(
                $io,
                'Rejected files',
                $this->rejectedFiles,
            );
        }

        if ($this->dryRun) {
            $io->note('Dry run: nothing was written.');

            return;
        }

        $io->success('Migration complete.');
    }

    /**
     * @param list<string> $items
     */
    private function listing(
        SymfonyStyle $io,
        string $title,
        array $items,
    ): void {
        if ([] === $items) {
            return;
        }

        $io->section($title);
        $io->listing($items);
    }
}
