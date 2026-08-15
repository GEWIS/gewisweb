<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Frontpage;

use App\Service\Frontpage\PageContentSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function str_repeat;
use function strlen;
use function strval;

/**
 * A custom page is stored as HTML and rendered as-is, so what the sanitizer lets through is the whole of what stands
 * between whoever writes a page and whoever reads it. These pin the configuration itself: a permission quietly
 * widened, or a restriction quietly dropped, shows up here rather than on somebody's screen.
 */
final class PageContentSanitizerTest extends KernelTestCase
{
    #[DataProvider('nothingGetsThrough')]
    public function testWhatCannotReachAReader(
        string $written,
        string $mustNotContain,
    ): void {
        self::assertStringNotContainsString(
            $mustNotContain,
            strval($this->sanitizer()->sanitize($written)),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nothingGetsThrough(): iterable
    {
        yield 'a script' => [
            '<p>Hello</p><script>alert(1)</script>',
            'script',
        ];

        yield 'a handler on an allowed element' => [
            '<p onclick="alert(1)">Hello</p>',
            'onclick',
        ];

        yield 'a javascript link' => [
            '<a href="javascript:alert(1)">Press</a>',
            'javascript:',
        ];

        yield 'a form' => [
            '<form action="/steal"><input name="password"></form>',
            '<form',
        ];

        yield 'an object' => [
            '<object data="evil.swf"></object>',
            '<object',
        ];

        yield 'a frame from somewhere else' => [
            '<iframe src="https://evil.example.com/embed"></iframe>',
            'evil.example.com',
        ];

        yield 'an image from somewhere else' => [
            '<img src="https://evil.example.com/track.gif" alt="">',
            'evil.example.com',
        ];

        // An image is served from our own pipeline or it is not served: one from a host we do allow a video to be
        // framed from is still an image nothing here resizes or caches.
        yield 'an image from a host we frame video from' => [
            '<img src="https://player.vimeo.com/poster.jpg" alt="">',
            'player.vimeo.com',
        ];

        yield 'an image pasted into the page itself' => [
            '<img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="">',
            'data:image',
        ];
    }

    #[DataProvider('thisGetsThrough')]
    public function testWhatAPageMayContain(
        string $written,
        string $mustContain,
    ): void {
        self::assertStringContainsString(
            $mustContain,
            strval($this->sanitizer()->sanitize($written)),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function thisGetsThrough(): iterable
    {
        // The editor styles with inline rules and classes, so a page stripped of them would come out unrecognisable.
        yield 'a class' => [
            '<p class="lead">Hello</p>',
            'class="lead"',
        ];

        yield 'an inline style' => [
            '<p style="color: red">Hello</p>',
            'style="color: red"',
        ];

        yield 'a table' => [
            '<table><tbody><tr><td>One</td></tr></tbody></table>',
            '<td>One</td>',
        ];

        // Images uploaded through the editor are served from our own pipeline, so they arrive site-relative.
        yield 'an image of ours' => [
            '<img src="/img/w1280/page-images/ab/abcdef.jpg" alt="A photo">',
            '/img/w1280/page-images/ab/abcdef.jpg',
        ];

        // The video hosts the content security policy already allows to be framed.
        yield 'a video we allow to be framed' => [
            '<iframe src="https://www.youtube-nocookie.com/embed/abc" allowfullscreen></iframe>',
            'youtube-nocookie.com',
        ];

        yield 'a link to another site' => [
            '<a href="https://gewis.nl">GEWIS</a>',
            'https://gewis.nl',
        ];

        yield 'a link within this one' => [
            '<a href="/en/association">The association</a>',
            '/en/association',
        ];

        // The address itself comes out with its @ escaped, which is the sanitizer being careful rather than the
        // scheme being refused.
        yield 'a mail link' => [
            '<a href="mailto:web@gewis.nl">Write to us</a>',
            'mailto:web',
        ];
    }

    /**
     * The sanitizer stops reading after a length it is told, and the default is short enough that a real page would be
     * cut off halfway. Losing the end of somebody's work without saying so is the failure this guards against.
     */
    public function testALongPageIsNotCutOffHalfway(): void
    {
        $written = '<p>' . str_repeat(
            'This is a sentence that a long page is made of. ',
            1000,
        ) . '</p><p>The end.</p>';

        self::assertGreaterThan(
            20000,
            strlen($written),
        );
        self::assertStringContainsString(
            'The end.',
            strval($this->sanitizer()->sanitize($written)),
        );
    }

    public function testNothingStaysNothing(): void
    {
        self::assertNull($this->sanitizer()->sanitize(null));
    }

    private function sanitizer(): PageContentSanitizer
    {
        self::bootKernel();

        return self::getContainer()->get(PageContentSanitizer::class);
    }
}
