<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\CommonMark\NoImage\NoImageExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\MarkdownConverter;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use function strip_tags;

/**
 * Renders Markdown to safe HTML: raw HTML is escaped, unsafe links are dropped, external links open safely in a new
 * window, and images are not allowed (see {@link NoImageExtension}).
 */
final class MarkdownExtension extends AbstractExtension
{
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
        ];
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
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new NoImageExtension());

        return $this->converter = new MarkdownConverter($environment);
    }
}
