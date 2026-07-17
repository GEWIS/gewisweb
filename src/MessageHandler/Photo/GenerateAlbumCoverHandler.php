<?php

declare(strict_types=1);

namespace App\MessageHandler\Photo;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use App\Message\Photo\GenerateAlbumCoverMessage;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\Repository\Photo\AlbumRepository;
use App\Service\Application\ImageUrlBuilder;
use App\Service\Photo\AlbumCoverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

#[AsMessageHandler]
class GenerateAlbumCoverHandler
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumCoverService $albumCoverService,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly HubInterface $hub,
        private readonly ImageUrlBuilder $imageUrlBuilder,
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

        $topic = sprintf(
            'photo/album/%d/cover',
            $message->getAlbumId(),
        );

        // Always tell the manage view the outcome so it can stop waiting; an album without photos yields no cover.
        if (null === $coverPath) {
            $this->hub->publish(new Update(
                $topic,
                json_encode(
                    ['status' => 'empty'],
                    JSON_THROW_ON_ERROR,
                ),
                private: true,
            ));

            return;
        }

        // The variant is generated on-demand on first hit.
        $this->hub->publish(new Update(
            $topic,
            json_encode(
                [
                    'status' => 'ready',
                    'url' => $this->imageUrlBuilder->url(
                        $coverPath,
                        ImageVariant::Cover,
                    ),
                ],
                JSON_THROW_ON_ERROR,
            ),
            private: true,
        ));

        // Pre-generate the cover's own variants (the 640x360 and 1280x720 landscape crops the cards use).
        $this->messageBus->dispatch(new ProcessImageVariantsMessage($coverPath, ImageProfile::AlbumCover));
    }
}
