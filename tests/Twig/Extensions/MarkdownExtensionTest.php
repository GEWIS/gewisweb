<?php

declare(strict_types=1);

namespace App\Tests\Twig\Extensions;

use App\Twig\Extensions\MarkdownExtension;
use PHPUnit\Framework\TestCase;

/**
 * A comment is written with four buttons, three of which Markdown has syntax for. The fourth writes a `<u>` tag into
 * text that is otherwise escaped from end to end, so these pin what does and does not become an underline again.
 */
final class MarkdownExtensionTest extends TestCase
{
    private function comment(string $text): string
    {
        return new MarkdownExtension()->markdownComment($text);
    }

    public function testUnderlinesWhatTheEditorWrote(): void
    {
        self::assertSame(
            "<p>Really <u>not</u> done</p>\n",
            $this->comment('Really <u>not</u> done'),
        );
    }

    /**
     * A member explaining how the editor writes an underline is showing the tag, not using it.
     */
    public function testLeavesTheTagAloneInsideCode(): void
    {
        self::assertSame(
            "<p>Write <code>&lt;u&gt;this&lt;/u&gt;</code> for it</p>\n",
            $this->comment('Write `<u>this</u>` for it'),
        );
    }

    /**
     * An opening tag whose closing tag never came would otherwise underline everything after the comment.
     */
    public function testLeavesAnUnpairedTagAsText(): void
    {
        self::assertSame(
            "<p>Half a tag &lt;u&gt; and no more</p>\n",
            $this->comment('Half a tag <u> and no more'),
        );
    }

    public function testAdmitsNoAttributesAndNoOtherTags(): void
    {
        self::assertSame(
            "<p>&lt;u onclick=\"x\"&gt;no&lt;/u&gt;</p>\n",
            $this->comment('<u onclick="x">no</u>'),
        );
        self::assertSame(
            "&lt;script&gt;alert()&lt;/script&gt;\n",
            $this->comment('<script>alert()</script>'),
        );
    }
}
