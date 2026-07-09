<?php

declare(strict_types=1);

namespace App\Twig\Components\Concerns;

use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * Adds a flash message from a live component. The consuming component must provide a promoted
 * `private readonly RequestStack $requestStack`; the no-op when the session has no flash bag matches a stateless
 * request, where there is nowhere to flash to.
 */
trait FlashesTrait
{
    private function flash(
        string $type,
        string $message,
    ): void {
        $session = $this->requestStack->getSession();
        if (!($session instanceof FlashBagAwareSessionInterface)) {
            return;
        }

        $session->getFlashBag()->add(
            $type,
            $message,
        );
    }
}
