<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use Jfcherng\Diff\DiffHelper;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

use function htmlspecialchars;
use function nl2br;

use const ENT_QUOTES;

/**
 * Renders a coloured field-level text diff for the revision compare view, wrapping jfcherng/php-diff. Used to show
 * the board what changed between two revisions (e.g. the previous revision's name vs the current one).
 */
class DiffExtension extends AbstractExtension
{
    private const array DIFFER_OPTIONS = ['context' => 2];

    private const array RENDERER_OPTIONS = [
        'detailLevel' => 'word',
        'showHeader' => false,
        'lineNumbers' => false,
    ];

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'diff',
                $this->diff(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'diff_styles',
                static fn (): string => DiffHelper::getStyleSheet(),
                ['is_safe' => ['html']],
            ),
        ];
    }

    /**
     * Render the difference between two (possibly null) strings as HTML. Returns empty markup only when both sides
     * are empty, so an unchanged optional field renders as nothing; an unchanged *non-empty* field renders its value
     * (jfcherng's renderers emit nothing for identical input, which would otherwise leave the field blank).
     *
     * Returns {@see Markup} rather than a string so the HTML stays unescaped even when a template captures the result
     * with `{% set %}` first (`is_safe` only covers direct interpolation).
     *
     * @param string $renderer one of the jfcherng HTML renderers, e.g. 'Combined' or 'Inline'
     */
    public function diff(
        ?string $old,
        ?string $new,
        string $renderer = 'Combined',
    ): Markup {
        $old ??= '';
        $new ??= '';

        if (
            '' === $old
            && '' === $new
        ) {
            return new Markup(
                '',
                'UTF-8',
            );
        }

        if ($old === $new) {
            return new Markup(
                nl2br(htmlspecialchars($old, ENT_QUOTES)),
                'UTF-8',
            );
        }

        return new Markup(
            DiffHelper::calculate(
                $old,
                $new,
                $renderer,
                self::DIFFER_OPTIONS,
                self::RENDERER_OPTIONS,
            ),
            'UTF-8',
        );
    }
}
