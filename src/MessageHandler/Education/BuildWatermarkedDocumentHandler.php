<?php

declare(strict_types=1);

namespace App\MessageHandler\Education;

use App\Message\Education\BuildWatermarkedDocumentMessage;
use App\Repository\Education\CourseDocumentDownloadRepository;
use App\Service\Education\CourseDocumentDownloadService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
class BuildWatermarkedDocumentHandler
{
    public function __construct(
        private readonly CourseDocumentDownloadRepository $downloadRepository,
        private readonly CourseDocumentDownloadService $downloadService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(BuildWatermarkedDocumentMessage $message): void
    {
        $download = $this->downloadRepository->find($message->getDownloadId());
        if (null === $download) {
            // The request expired or was purged before a worker picked it up; nothing to build.
            return;
        }

        try {
            $this->downloadService->build($download);
        } catch (Throwable $e) {
            // Mark the request failed before rethrowing, so the page waiting on it stops waiting even though the
            // message goes on to be retried and then to the failure transport.
            $this->downloadService->markFailed($download);

            $this->logger->error(
                'Could not build watermarked download {download}: {reason}',
                [
                    'download' => $message->getDownloadId(),
                    'reason' => $e->getMessage(),
                ],
            );

            throw $e;
        }
    }
}
