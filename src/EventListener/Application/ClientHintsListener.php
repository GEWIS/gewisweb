<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Asks the browser to send the client hints that say which device somebody is on.
 *
 * The low-entropy hints (the browser brand, whether it is mobile, the platform) arrive unasked; these are the ones that
 * have to be requested. They are what tells Windows 11 apart from Windows 10, since the user agent says
 * `Windows NT 10.0` for both and always will.
 *
 * Only browsers that implement client hints answer, and only over a secure context, so nothing here is guaranteed to
 * come back. The user agent stays the fallback.
 */
#[AsEventListener(event: ResponseEvent::class)]
final readonly class ClientHintsListener
{
    private const string ACCEPTED = 'Sec-CH-UA-Platform-Version, Sec-CH-UA-Full-Version-List, Sec-CH-UA-Model, '
        . 'Sec-CH-UA-Arch, Sec-CH-UA-Bitness';

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getResponse()->headers->set(
            'Accept-CH',
            self::ACCEPTED,
        );
    }
}
