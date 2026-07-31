<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Meeting;
use App\Repository\Decision\MeetingDocumentRepository;
use App\Service\Application\FileStorage;
use RuntimeException;
use ZipArchive;

use function sprintf;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;

/**
 * Builds a temporary ZIP with the latest version of every document of a meeting, entries prefixed with their agenda
 * point number. The caller streams the file and deletes it after sending.
 */
final readonly class MeetingDocumentZipBuilder
{
    public function __construct(
        private FileStorage $fileStorage,
        private MeetingDocumentRepository $meetingDocumentRepository,
    ) {
    }

    /**
     * @return ?string the path of the temporary ZIP, or `null` when the meeting has no documents
     */
    public function build(Meeting $meeting): ?string
    {
        $entries = [];
        foreach ($this->meetingDocumentRepository->findForMeeting($meeting) as $document) {
            $latest = $document->getLatestVersion();
            if (null === $latest) {
                continue;
            }

            $entries[] = [
                $document,
                $latest,
            ];
        }

        if ([] === $entries) {
            return null;
        }

        $zipPath = tempnam(
            sys_get_temp_dir(),
            'meeting-documents-',
        );
        if (false === $zipPath) {
            throw new RuntimeException('Could not create a temporary file for the document archive.');
        }

        $zip = new ZipArchive();
        if (
            true !== $zip->open(
                $zipPath,
                ZipArchive::OVERWRITE,
            )
        ) {
            throw new RuntimeException('Could not open the temporary document archive.');
        }

        $used = [];
        foreach ($entries as [$document, $version]) {
            $name = sprintf(
                '%s (%s)',
                $document->getName(),
                $version->getVersionLabel(),
            );

            $point = $document->getPoint();
            if (null !== $point) {
                $name = sprintf(
                    '%s. %s',
                    $point->getNumber(),
                    $name,
                );
            }

            // Document names are free text; slashes would nest the entry into directories.
            $name = str_replace(
                [
                    '/',
                    '\\',
                ],
                '-',
                $name,
            );

            $entryName = $name . '.pdf';
            $counter = 2;
            while (isset($used[$entryName])) {
                $entryName = sprintf(
                    '%s (%d).pdf',
                    $name,
                    $counter,
                );
                $counter++;
            }

            $used[$entryName] = true;
            $zip->addFromString(
                $entryName,
                $this->fileStorage->read($version->getPath()),
            );
        }

        $zip->close();

        return $zipPath;
    }
}
