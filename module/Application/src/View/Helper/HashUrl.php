<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Application\Model\Enums\Languages;
use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Helper\ServerUrl;

use function implode;
use function preg_replace;

/**
 * View helper to generate URLs of the current page with a hash `#`.
 */
class HashUrl extends AbstractHelper
{
    public function __construct(private readonly ServerUrl $serverUrlHelper)
    {
    }

    public function __invoke(): string
    {
        $path = null;
        if (isset($_SERVER['REQUEST_URI'])) {
            // Drop `/index.php` if it exists.
            $path = preg_replace(
                '/^((\/' . implode('|', Languages::stringValues()) . ')?\/index\.php)/',
                '',
                $_SERVER['REQUEST_URI'],
            );
        }

        return $this->serverUrlHelper->__invoke($path) . '#';
    }
}
