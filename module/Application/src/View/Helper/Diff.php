<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Jfcherng\Diff\DiffHelper;
use Laminas\View\Helper\AbstractHelper;

class Diff extends AbstractHelper
{
    public const string DIFF_RENDER_COMBINED = 'Combined';
    public const string DIFF_RENDER_INLINE = 'Inline';
    public const string DIFF_RENDER_SIDE_BY_SIDE = 'SideBySide';

    /**
     * While `php-diff` has a `RendererFactory` it requires the renderer type to be provided, this would mean that
     * we have to inject three different instances of the renderer. This is not really a better solution than just
     * creating the renderers when invoked.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __invoke(
        ?string $old,
        ?string $new,
        string $renderer = self::DIFF_RENDER_COMBINED,
        array $rendererOverwrites = [],
        array $differOverwrites = [],
    ): ?string {
        // We accept `null` values from `LocalisedText`.
        $old ??= '';
        $new ??= '';

        // If both the old and the new are empty we do not need to output anything.
        if (
            '' === $old
            && '' === $new
        ) {
            return '';
        }

        // The "Combined" renderer is used solely for single-line strings, so we change the `detailLevel` to force this.
        if (
            self::DIFF_RENDER_COMBINED === $renderer
            && !isset($rendererOverwrites['detailLevel'])
        ) {
            $rendererOverwrites['detailLevel'] = 'line';
        }

        return DiffHelper::calculate(
            $old,
            $new,
            $renderer,
            $differOverwrites + $this->config['differ'],
            $rendererOverwrites + $this->config['renderer'],
        );
    }
}
