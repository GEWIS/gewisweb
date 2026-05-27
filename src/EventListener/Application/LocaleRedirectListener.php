<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(
    event: KernelEvents::REQUEST,
    priority: 160,
)]
final readonly class LocaleRedirectListener
{
    /**
     * @param string[] $supportedLocales
     */
    public function __construct(
        private string $defaultLocale,
        private array $supportedLocales,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ('/' !== $request->getPathInfo()) {
            return;
        }

        $preferred = $request->getPreferredLanguage($this->supportedLocales) ?? $this->defaultLocale;

        $event->setResponse(new RedirectResponse('/' . $preferred . '/'));
    }
}
