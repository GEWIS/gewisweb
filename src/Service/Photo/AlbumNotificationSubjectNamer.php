<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Application\Enums\NotificationType;
use App\Repository\Photo\AlbumRepository;
use App\Service\Application\AbstractNotificationSubjectNamer;
use Override;

final class AlbumNotificationSubjectNamer extends AbstractNotificationSubjectNamer
{
    public function __construct(private readonly AlbumRepository $albumRepository)
    {
    }

    #[Override]
    public function supports(NotificationType $type): bool
    {
        return NotificationType::AlbumPublished === $type;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function namesFor(
        NotificationType $type,
        array $subjectIds,
    ): array {
        $names = [];
        foreach ($this->albumRepository->findBy(['id' => $subjectIds]) as $album) {
            $id = $album->getId();
            if (null === $id) {
                continue;
            }

            $names[$id] = $this->plain($album->getName());
        }

        return $names;
    }
}
