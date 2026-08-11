<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\StorageNamespace;
use App\Message\Photo\ProcessImageVariantsMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * Taking an image somebody uploaded into storage and queueing the sizes the pages will ask for, which is the same three
 * steps whatever the image is for: store, queue, and on any failure reclaim whatever this call freshly wrote.
 *
 * A subclass only says which namespace, scope and {@see ImageProfile} its images belong to, and decides what the
 * returned path becomes. Nothing here publishes anything: a path on a draft revision still has to get through review.
 */
abstract readonly class AbstractImageUploadService
{
    public function __construct(
        protected FileStorage $fileStorage,
        protected MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param string $localPath a readable path on this machine: an upload's temporary file, or something derived from
     *                          one
     *
     * @return string|null the stored path, or null when the file could not be stored
     */
    protected function storeAndQueue(
        StorageNamespace $namespace,
        string $localPath,
        ?string $scope,
        ImageProfile $profile,
    ): ?string {
        $stored = null;

        try {
            // store() validates the detected MIME type and the size limit, and de-duplicates by content hash. A scoped
            // namespace keeps the same bytes uploaded by two owners on their own paths.
            $stored = $this->fileStorage->store(
                $namespace,
                $localPath,
                $scope,
            );

            $this->messageBus->dispatch(new ProcessImageVariantsMessage(
                $stored->path,
                $profile,
            ));

            return $stored->path;
        } catch (Throwable) {
            // Reclaim the bytes this call freshly wrote; a pre-existing (de-duplicated) file is left alone, since
            // something else still points at it.
            if (
                null !== $stored
                && !$stored->deduplicated
            ) {
                $this->fileStorage->remove($stored->path);
            }

            return null;
        }
    }
}
