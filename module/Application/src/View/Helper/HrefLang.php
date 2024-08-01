<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Application\Model\Enums\Languages;
use InvalidArgumentException;
use Laminas\View\Helper\Placeholder\Container\AbstractStandalone;

use function is_string;
use function sprintf;

/**
 * Helper for setting `alternate` `hreflang` tags. The normal `HeadLink` view helper of Laminas only allow us to set one
 * and then overwrite only one tag, while we need more.
 */
class HrefLang extends AbstractStandalone
{
    /**
     * Set a specific `hreflang`.
     *
     * @psalm-param Languages|'x-default' $language
     *
     * @return $this
     */
    public function setHrefLang(
        Languages|string $language,
        string $url,
    ): self {
        if (
            is_string($language) // @phpstan-ignore booleanAnd.alwaysFalse (bad inference from 'x-default')
            && 'x-default' !== $language // @phpstan-ignore notIdentical.alwaysFalse (bad inference from 'x-default')
        ) {
            throw new InvalidArgumentException('Only \'x-default\' is supported as alternative to Languages.');
        }

        if ($language instanceof Languages) {
            $language = $language->value;
        }

        if (!$this->getContainer()->offsetExists($language)) {
            $this->getContainer()->offsetSet($language, $url);
        }

        return $this;
    }

    public function toString(): string
    {
        $output = '';

        foreach ($this as $language => $url) {
            $output .= sprintf(
                '<link rel="alternate" hreflang="%s" href="%s" />',
                $language,
                $url,
            );
        }

        return $output;
    }
}
