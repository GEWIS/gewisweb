<?php

declare(strict_types=1);

namespace App\Tests\Integration\MessageHandler\Photo;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use App\Entity\Application\Enums\StorageNamespace;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\MessageHandler\Photo\ProcessImageVariantsHandler;
use App\Service\Application\FileStorage;
use App\Service\Application\VariantGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

use function dirname;

/**
 * Confirms that a variant-processing message routes to the dedicated in-memory `images` transport (never the priority
 * or bulk queues) and that its handler generates the variants into shared storage. Storage is the in-memory flysystem
 * adapter, so the source stored here is visible to the container-shared handler.
 */
final class ProcessImageVariantsHandlerTest extends KernelTestCase
{
    public function testMessageRoutesToTheImagesTransport(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $source = $this->storeSource();
        $container->get(MessageBusInterface::class)->dispatch(
            new ProcessImageVariantsMessage(
                $source,
                ImageProfile::AlbumPhoto,
            ),
        );

        $transport = $container->get('messenger.transport.images');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );
        self::assertCount(
            1,
            $transport->getSent(),
        );
    }

    public function testHandlerGeneratesTheProfileVariants(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $source = $this->storeSource();
        $handler = $container->get(ProcessImageVariantsHandler::class);
        $handler(new ProcessImageVariantsMessage(
            $source,
            ImageProfile::AlbumPhoto,
        ));

        $generator = $container->get(VariantGenerator::class);
        self::assertTrue($generator->variantExists($source, ImageVariant::W320));
        self::assertTrue($generator->variantExists($source, ImageVariant::W640));
    }

    private function storeSource(): string
    {
        $storage = self::getContainer()->get(FileStorage::class);

        return $storage->store(
            StorageNamespace::PhotoOriginal,
            dirname(
                __DIR__,
                4,
            ) . '/tests/Resources/images/gala-dinner-1.jpg',
        )->path;
    }
}
