<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\Mvc\I18n\Translator;
use Laminas\View\Helper\AbstractHelper;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\MarkdownConverter;

class Markdown extends AbstractHelper
{
    public function __construct(
        private readonly Translator $translator,
        private readonly MarkdownConverter $defaultConverter,
        private readonly MarkdownConverter $companyConverter,
    ) {
    }

    /**
     * Parse Markdown and convert it to HTML.
     */
    public function __invoke(
        string $text,
        bool $company = false,
    ): string {
        try {
            if ($company) {
                return $this->companyConverter->convert($text)->getContent();
            }

            return $this->defaultConverter->convert($text)->getContent();
        } catch (CommonMarkException) {
            return $this->translator->translate('This text could not be generated.');
        }
    }
}
