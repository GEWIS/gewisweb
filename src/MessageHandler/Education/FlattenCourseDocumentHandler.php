<?php

declare(strict_types=1);

namespace App\MessageHandler\Education;

use App\Message\Education\FlattenCourseDocumentMessage;
use App\Repository\Education\CourseDocumentRepository;
use App\Service\Education\CourseDocumentFlattener;
use App\Service\Education\PdfRasterizerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FlattenCourseDocumentHandler
{
    public function __construct(
        private readonly CourseDocumentRepository $documentRepository,
        private readonly CourseDocumentFlattener $flattener,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(FlattenCourseDocumentMessage $message): void
    {
        $document = $this->documentRepository->find($message->getDocumentId());
        if (null === $document) {
            // The document was deleted between dispatch and handling; nothing to flatten.
            return;
        }

        try {
            $this->flattener->flatten($document);
        } catch (PdfRasterizerException $e) {
            // A document poppler cannot read is a bad upload, not a broken queue: record it and let an administrator
            // replace the file. Rethrowing would only retry the same unreadable bytes.
            $this->flattener->markFailed(
                $document,
                $e->getMessage(),
            );

            $this->logger->error(
                'Could not flatten course document {document}: {reason}',
                [
                    'document' => $message->getDocumentId(),
                    'reason' => $e->getMessage(),
                ],
            );
        }
    }
}
