<?php

declare(strict_types=1);

namespace App\MessageHandler\Photo;

use App\Entity\Application\Enums\ImageProfile;
use App\Message\Photo\GenerateAlbumCoverMessage;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\Repository\Photo\AlbumRepository;
use App\Service\Photo\AlbumCoverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class GenerateAlbumCoverHandler
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumCoverService $albumCoverService,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(GenerateAlbumCoverMessage $message): void
    {
        $album = $this->albumRepository->find($message->getAlbumId());
        if (null === $album) {
            // The album was deleted between dispatch and handling; nothing to cover.
            return;
        }

        $coverPath = $this->albumCoverService->generateForAlbum($album);
        $this->entityManager->flush();

        if (null === $coverPath) {
            return;
        }

        // Pre-generate the cover's own variants (the 640x360 and 1280x720 landscape crops the cards use).
        $this->messageBus->dispatch(new ProcessImageVariantsMessage($coverPath, ImageProfile::AlbumCover));
    }
}
