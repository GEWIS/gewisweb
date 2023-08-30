<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Application\Model\Enums\Languages;
use Laminas\View\Helper\Placeholder\Container\AbstractStandalone;

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
     * @return $this
     */
    public function setHrefLang(
        Languages $language,
        string $url,
    ): self {
        if (!$this->getContainer()->offsetExists($language->value)) {
            $this->getContainer()->offsetSet($language->value, $url);
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
