<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Education\CourseDocument;
use App\Entity\Education\CourseDocumentDownload;
use App\Entity\Education\Enums\DownloadStatus;
use App\Entity\Education\Exam;
use App\Entity\Education\Summary;
use App\Entity\User\User;
use App\Message\Education\BuildWatermarkedDocumentMessage;
use App\Repository\Education\CourseDocumentDownloadRepository;
use App\Service\Application\FileStorage;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;

use function count;
use function implode;
use function sprintf;

/**
 * Building a watermarked copy means compositing and re-encoding every page, which is fast but not instant. Doing that
 * inline is what used to exhaust the PHP time limit on a long exam, so nothing here is served from the request thread.
 */
final readonly class CourseDocumentDownloadService
{
    /**
     * How long a request and anything built for it is kept. A build takes about a second and the waiting page collects
     * it as soon as it is ready, so a minute already covers the whole exchange; asking again costs another second.
     */
    private const string RETENTION = 'PT1M';

    public function __construct(
        private FileStorage $fileStorage,
        private WatermarkedPdfBuilder $pdfBuilder,
        private CourseDocumentDownloadRepository $downloadRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private SluggerInterface $slugger,
    ) {
    }

    public function request(
        CourseDocument $document,
        ?User $user,
        ?string $clientIp,
    ): CourseDocumentDownload {
        $download = new CourseDocumentDownload();
        $download->setToken(Uuid::v4());
        $download->setDocument($document);
        $download->setRequestedBy($user);
        // An anonymous request from campus still has to be attributable, so the watermark names its address.
        $download->setRequestedByName($user?->getDisplayName() ?? $clientIp ?? 'an anonymous visitor');
        $download->setRequestedFrom($clientIp ?? '');
        $download->setRequestedAt(new DateTime());

        $this->entityManager->persist($download);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new BuildWatermarkedDocumentMessage($download->getId() ?? 0));

        return $download;
    }

    public function build(CourseDocumentDownload $download): void
    {
        $path = sprintf(
            '%s/%s.pdf',
            StorageNamespace::EducationDownload->directory(),
            $download->getToken()->toRfc4122(),
        );

        $this->fileStorage->write(
            $path,
            $this->pdfBuilder->build($download),
        );

        $download->setPath($path);
        $download->setStatus(DownloadStatus::Ready);
        $this->entityManager->flush();
    }

    public function markFailed(CourseDocumentDownload $download): void
    {
        $download->setStatus(DownloadStatus::Failed);
        $this->entityManager->flush();
    }

    public function markCollected(CourseDocumentDownload $download): void
    {
        $download->setCollectedAt(new DateTime());
        $this->entityManager->flush();
    }

    public function purgeExpired(): int
    {
        $expired = $this->downloadRepository->findExpired(
            new DateTime()->sub(new DateInterval(self::RETENTION)),
        );

        foreach ($expired as $download) {
            $path = $download->getPath();

            $this->entityManager->remove($download);
            $this->entityManager->flush();

            if (null === $path) {
                continue;
            }

            $this->fileStorage->remove($path);
        }

        return count($expired);
    }

    /**
     * Mirrors the previous site, so a member's own archive keeps sorting the way it used to.
     */
    public function filenameFor(CourseDocument $document): string
    {
        $parts = [$document->getCourse()->getCode()];

        if ($document instanceof Summary) {
            $author = $document->getAuthor();
            if (null !== $author) {
                $parts[] = $this->slugger->slug($author)->toString();
            }

            $parts[] = 'summary';
        } elseif ($document instanceof Exam) {
            $parts[] = $document->getExamType()->value;
        }

        $parts[] = $document->getDate()->format('Y-m-d');

        return implode(
            '-',
            $parts,
        ) . '.pdf';
    }
}
