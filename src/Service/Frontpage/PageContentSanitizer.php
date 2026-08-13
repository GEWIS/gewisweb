<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * What a custom page is allowed to contain, applied on the way in.
 *
 * A page is written in a visual editor and stored as HTML, which the public template renders as-is. That makes the
 * moment of saving the only place worth checking: whatever is stored has already been through here, so a page that
 * was written before this existed, or by somebody bypassing the editor, cannot smuggle anything past the reader.
 */
final readonly class PageContentSanitizer
{
    public function __construct(
        #[Autowire(service: 'html_sanitizer.sanitizer.app.page_sanitizer')]
        private HtmlSanitizerInterface $sanitizer,
    ) {
    }

    public function sanitize(?string $content): ?string
    {
        if (null === $content) {
            return null;
        }

        return $this->sanitizer->sanitize($content);
    }
}
