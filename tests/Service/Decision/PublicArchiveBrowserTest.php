<?php

declare(strict_types=1);

namespace App\Tests\Service\Decision;

use App\Service\Decision\PublicArchiveBrowser;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function array_map;
use function sys_get_temp_dir;
use function uniqid;

final class PublicArchiveBrowserTest extends TestCase
{
    private string $root;
    private PublicArchiveBrowser $browser;

    #[Override]
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/public-archive-' . uniqid();

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->root . '/Policies & Regulations');
        $filesystem->dumpFile(
            $this->root . '/Policies & Regulations/Key Policy.pdf',
            '%PDF-1.4',
        );
        $filesystem->dumpFile(
            $this->root . '/Annual Reports.pdf',
            '%PDF-1.4',
        );
        $filesystem->dumpFile(
            $this->root . '/.hidden',
            'secret',
        );

        $this->browser = new PublicArchiveBrowser($this->root);
    }

    #[Override]
    protected function tearDown(): void
    {
        new Filesystem()->remove($this->root);
    }

    public function testListsFoldersFirstAndHidesDotEntries(): void
    {
        $entries = $this->browser->listDirectory('');
        self::assertNotNull($entries);

        self::assertSame(
            [
                'Policies & Regulations',
                'Annual Reports.pdf',
            ],
            array_map(
                static fn (array $entry): string => $entry['name'],
                $entries,
            ),
        );
        self::assertTrue($entries[0]['isDirectory']);
        self::assertFalse($entries[1]['isDirectory']);
    }

    public function testListsASubdirectoryByItsUrlPath(): void
    {
        $entries = $this->browser->listDirectory('Policies & Regulations');
        self::assertNotNull($entries);
        self::assertSame(
            'Policies & Regulations/Key Policy.pdf',
            $entries[0]['path'],
        );
        self::assertTrue($this->browser->isFile($entries[0]['path']));
    }

    public function testRejectsTraversalHiddenAndUnknownPaths(): void
    {
        self::assertNull($this->browser->listDirectory('..'));
        self::assertNull($this->browser->listDirectory('Policies & Regulations/..'));
        self::assertNull($this->browser->listDirectory('nope'));
        self::assertFalse($this->browser->isFile('.hidden'));
        self::assertFalse($this->browser->isFile('../etc/passwd'));
        self::assertFalse($this->browser->isFile('Policies & Regulations'));
    }
}
