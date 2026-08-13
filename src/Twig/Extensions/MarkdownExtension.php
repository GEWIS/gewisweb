<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\CommonMark\NoImage\NoImageExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use function html_entity_decode;
use function mb_strlen;
use function mb_substr;
use function preg_replace_callback;
use function strip_tags;
use function trim;

/**
 * Renders Markdown to safe HTML: raw HTML is escaped, unsafe links are dropped, external links open safely in a new
 * window, and images are not allowed (see {@link NoImageExtension}).
 */
final class MarkdownExtension extends AbstractExtension
{
    /** How much of an article an excerpt is taken from: well past the longest one any list row shows. */
    private const int EXCERPT_SOURCE_LENGTH = 1500;

    private ?MarkdownConverter $converter = null;

    /**
     * @return TwigFilter[]
     */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'markdown',
                $this->markdown(...),
                ['is_safe' => ['html']],
            ),
            new TwigFilter(
                'markdown_comment',
                $this->markdownComment(...),
                ['is_safe' => ['html']],
            ),
            new TwigFilter(
                'markdown_excerpt',
                $this->markdownExcerpt(...),
            ),
        ];
    }

    /**
     * The opening words of a piece of Markdown as plain text, for a list row that shows what an article is about
     * before the reader opens it. Longer than the excerpt gets an ellipsis.
     *
     * What comes back is text and not HTML: the escaping the converter did is undone, so an ampersand somebody wrote
     * is an ampersand again by the time Twig escapes it the once. Deliberately not marked safe for that reason.
     */
    public function markdownExcerpt(
        ?string $text,
        int $length,
    ): string {
        // Only the opening of the article is converted: a list row should not pay for a full render of a long one.
        $body = trim(html_entity_decode(strip_tags($this->markdown(
            mb_substr(
                $text ?? '',
                0,
                self::EXCERPT_SOURCE_LENGTH,
            ),
        ))));

        if (mb_strlen($body) <= $length) {
            return $body;
        }

        return trim(mb_substr(
            $body,
            0,
            $length,
        )) . '…';
    }

    /**
     * A comment written with the four-button editor: bold, italic, underline and strikethrough, and nothing else.
     *
     * Underline is the one of the four that GitHub-flavoured Markdown has no syntax for, so the editor writes it as a
     * `<u>` tag. Everything the converter is handed is escaped first and its output narrowed to the handful of tags a
     * comment may carry; only that exact tag is put back afterwards, so no attribute and no other tag can come
     * through: `&lt;u onclick=...&gt;` simply does not match.
     */
    public function markdownComment(?string $text): string
    {
        return $this->underline($this->markdown(
            $text,
            [
                'p',
                'br',
                'em',
                'strong',
                'del',
                'u',
                'code',
                'a',
            ],
        ));
    }

    /**
     * Turns the escaped `<u>` tags back into real ones, in pairs and never inside code: a member showing what the tag
     * looks like keeps it as text, and an opening tag whose closing tag never came stays text as well rather than
     * underlining everything after the comment.
     */
    private function underline(string $html): string
    {
        return preg_replace_callback(
            '#<code[^>]*>.*?</code>|&lt;u&gt;(.*?)&lt;/u&gt;#s',
            /** @param string[] $match */
            static fn (array $match): string => isset($match[1])
                ? '<u>' . $match[1] . '</u>'
                : $match[0],
            $html,
        ) ?? $html;
    }

    /**
     * Render Markdown to safe HTML.
     *
     * When $allowedTags is given (bare tag names, e.g. ['p', 'em', 'strong', 'a']), every other tag is stripped from
     * the output. This is used on the activity overview to keep descriptions to inline formatting.
     *
     * @param string[]|null $allowedTags
     */
    public function markdown(
        ?string $text,
        ?array $allowedTags = null,
    ): string {
        if (
            null === $text
            || '' === $text
        ) {
            return '';
        }

        $html = $this->getConverter()->convert($text)->getContent();

        if (null !== $allowedTags) {
            $html = strip_tags(
                $html,
                $allowedTags,
            );
        }

        return $html;
    }

    private function getConverter(): MarkdownConverter
    {
        if (null !== $this->converter) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'renderer' => ['soft_break' => '<br>'],
            'commonmark' => [
                'enable_em' => true,
                'enable_strong' => true,
                'use_asterisk' => true,
                'use_underscore' => true,
                'unordered_list_markers' => [
                    '-',
                    '*',
                    '+',
                ],
            ],
            'external_link' => [
                'internal_hosts' => 'gewis.nl',
                'open_in_new_window' => true,
                'html_class' => 'external-link',
                'nofollow' => '',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
            // Bootstrap-styled tables (as before): every rendered <table> gets these classes.
            'default_attributes' => [
                Table::class => [
                    'class' => 'table table-bordered',
                ],
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new ExternalLinkExtension());
        // GFM tables and strikethrough: rendered to match the editor's toolbar (the on-site description uses them;
        // the restricted-tag variants simply strip what they do not list).
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new DefaultAttributesExtension());
        $environment->addExtension(new NoImageExtension());

        return $this->converter = new MarkdownConverter($environment);
    }
}
