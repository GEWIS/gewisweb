<?php

declare(strict_types=1);

namespace App\Service\Application;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function fpassthru;
use function is_file;

/**
 * Builds a download {@see Response} for a stored file with a `Content-Disposition: attachment` and a caller-chosen
 * download filename (the original name, kept in the DB, not the content-addressed storage name). Reusable across photo
 * downloads and future PDF endpoints (meeting documents, exams).
 *
 * On local storage the file is handed to Caddy via X-Sendfile (a {@see BinaryFileResponse}); on a non-local adapter the
 * bytes are streamed.
 */
final readonly class FileDownloadHelper
{
    public function __construct(
        private FileStorage $fileStorage,
        #[Autowire('%kernel.project_dir%/data')]
        private string $storageRootDir,
    ) {
    }

    public function download(
        string $storedPath,
        string $downloadFilename,
        ?string $contentType = null,
    ): Response {
        $absolutePath = $this->storageRootDir . '/' . $storedPath;

        if (is_file($absolutePath)) {
            $response = new BinaryFileResponse($absolutePath);
        } else {
            $stream = $this->fileStorage->readStream($storedPath);
            $response = new StreamedResponse(static function () use ($stream): void {
                fpassthru($stream);
            });
        }

        if (null !== $contentType) {
            $response->headers->set(
                'Content-Type',
                $contentType,
            );
        }

        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $downloadFilename,
            ),
        );

        return $response;
    }
}
