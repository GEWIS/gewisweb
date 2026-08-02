<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Career;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Career\Company;
use App\Entity\Career\Enums\CompanyBannerFormats;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\Repository\Career\CompanyRepository;
use App\Service\Application\FileStorage;
use App\Service\Career\CompanyImageUploadService;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

use function count;
use function file_put_contents;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagejpeg;
use function str_contains;
use function strval;
use function sys_get_temp_dir;
use function tempnam;

/**
 * Storage is the in-memory adapter the test environment binds, so a stored file can be read back and a reclaimed one
 * asserted gone.
 */
final class CompanyImageUploadServiceTest extends DatabaseTestCase
{
    public function testALogoIsStoredUnderItsOwnCompanyAndQueuedForItsVariants(): void
    {
        $company = $this->company();

        $path = $this->service()->uploadLogo(
            $company,
            $this->imageFile(
                0x1E,
                0x7A,
                0x30,
            ),
        );

        self::assertIsString($path);
        self::assertTrue($this->storage()->exists($path));
        self::assertTrue(str_contains(
            $path,
            'career/' . strval($company->getId()) . '/images',
        ));
        self::assertSame(
            ImageProfile::CompanyLogo,
            $this->queuedFor($path)?->getProfile(),
        );
    }

    public function testABannerIsQueuedAgainstTheProfileOfTheFormatItWasBoughtIn(): void
    {
        $path = $this->service()->uploadBanner(
            $this->company(),
            $this->imageFile(
                0x40,
                0x10,
                0x90,
            ),
            CompanyBannerFormats::Billboard,
        );

        self::assertIsString($path);
        self::assertSame(
            ImageProfile::CompanyBannerBillboard,
            $this->queuedFor($path)?->getProfile(),
        );
    }

    public function testTwoCompaniesUploadingTheSameImageDoNotShareAFile(): void
    {
        $first = $this->service()->uploadLogo(
            $this->company('nexunt'),
            $this->imageFile(
                0x11,
                0x22,
                0x33,
            ),
        );
        $second = $this->service()->uploadLogo(
            $this->company('orbit-analytics'),
            $this->imageFile(
                0x11,
                0x22,
                0x33,
            ),
        );

        self::assertIsString($first);
        self::assertIsString($second);
        self::assertNotSame(
            $first,
            $second,
        );
    }

    public function testSomethingThatIsNotAnImageIsRefusedAndLeavesNothingBehind(): void
    {
        $before = $this->queuedMessages();

        self::assertNull($this->service()->uploadLogo(
            $this->company(),
            $this->textFile(),
        ));
        self::assertCount(
            $before,
            [...$this->transport()->getSent()],
        );
    }

    private function queuedFor(string $path): ?ProcessImageVariantsMessage
    {
        foreach ($this->transport()->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if (
                !$message instanceof ProcessImageVariantsMessage
                || $message->getSourcePath() !== $path
            ) {
                continue;
            }

            return $message;
        }

        return null;
    }

    private function queuedMessages(): int
    {
        return count([...$this->transport()->getSent()]);
    }

    private function transport(): InMemoryTransport
    {
        $transport = self::getContainer()->get('messenger.transport.images');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );

        return $transport;
    }

    private function imageFile(
        int $red,
        int $green,
        int $blue,
    ): UploadedFile {
        $path = tempnam(
            sys_get_temp_dir(),
            'gewisweb-company-image-test',
        );
        self::assertIsString($path);

        $image = imagecreatetruecolor(
            48,
            32,
        );
        self::assertNotFalse($image);
        $colour = imagecolorallocate(
            $image,
            $red,
            $green,
            $blue,
        );
        self::assertNotFalse($colour);
        imagefilledrectangle(
            $image,
            0,
            0,
            48,
            32,
            $colour,
        );
        imagejpeg(
            $image,
            $path,
        );

        return new UploadedFile(
            $path,
            'logo.jpg',
            'image/jpeg',
            null,
            true,
        );
    }

    private function textFile(): UploadedFile
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'gewisweb-company-image-test',
        );
        self::assertIsString($path);
        file_put_contents(
            $path,
            'this is not an image',
        );

        return new UploadedFile(
            $path,
            'notes.txt',
            'text/plain',
            null,
            true,
        );
    }

    private function company(string $slug = 'nexunt'): Company
    {
        $company = self::getContainer()->get(CompanyRepository::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        return $company;
    }

    private function storage(): FileStorage
    {
        return self::getContainer()->get(FileStorage::class);
    }

    private function service(): CompanyImageUploadService
    {
        return new CompanyImageUploadService(
            $this->storage(),
            self::getContainer()->get(MessageBusInterface::class),
        );
    }
}
