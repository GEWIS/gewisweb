<?php

declare(strict_types=1);

namespace Application\Router;

use Application\Model\Enums\Languages;
use Laminas\Http\Header\Accept\FieldValuePart\LanguageFieldValuePart;
use Laminas\Http\Header\AcceptLanguage;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\Session\Container as SessionContainer;
use Laminas\Stdlib\RequestInterface;

use function count;
use function explode;
use function in_array;
use function is_callable;
use function ltrim;
use function method_exists;
use function str_starts_with;
use function strlen;
use function substr;

class LanguageAwareTreeRouteStack extends TranslatorAwareTreeRouteStack
{
    private ?string $lastMatchedLanguage = null;

    /**
     * @inheritDoc
     */
    public function assemble(
        array $params = [],
        array $options = [],
    ): mixed {
        $translator = null;
        if (isset($options['translator'])) {
            $translator = $options['translator'];
        } elseif ($this->hasTranslator()) {
            $translator = $this->getTranslator();
        }

        // Store the original base URL.
        $oldBaseUrl = $this->getBaseUrl();

        // Try to get the language, because we do not have access to the current request in this method we cannot add an
        // `else` clause to call `$this->getLanguage()` to get the language.
        $language = null;
        if (isset($params['language'])) {
            // The language is already defined in the parameters for the route, so we can use that. This happens when
            // calling `url()` from a view while manually setting `['language' => '{language}']`.
            $language = $params['language'];
        } elseif (is_callable([$translator, 'getLocale'])) {
            // Otherwise, try to get the language from the translator. Note that `is_callable` is preferred here as
            // using `instanceof` can give incorrect results.
            $language = $translator->getLocale();
        }

        if (null !== $language) {
            // If we have a language, set the base URL to be the old one together with the language. This allows us to
            // use the other routers to properly assemble the remaining parts of the route. If we do not have a language
            // this is not a problem as we can already assemble the remaining parts of the route without having to
            // modify the base URL.
            $this->setBaseUrl($oldBaseUrl . '/' . $language);
        }

        // Assemble the remaining parts of the route (everything that comes after the language delimiter).
        $route = parent::assemble($params, $options);

        // Finally, we set the base URL back to its original value (without language).
        $this->setBaseUrl($oldBaseUrl);

        return $route;
    }

    /**
     * @inheritDoc
     */
    public function match(
        RequestInterface $request,
        $pathOffset = null,
        array $options = [],
    ): ?RouteMatch {
        if (!method_exists($request, 'getUri')) {
            return null;
        }

        if (
            null === $this->baseUrl
            && method_exists($request, 'getBaseUrl')
        ) {
            // While `baseUrl` may be typed to be `string` in the `TreeRouteStack` it is very likely to be `null` by
            // default. As such, we need to set it to the correct value based on the actual request.
            $this->setBaseUrl($request->getBaseUrl());
        }

        // Get the supported languages.
        $languages = Languages::stringValues();

        // Store the original base URL (likely only just set above).
        $oldBaseUrl = $this->getBaseUrl();

        // Get the path from the URI, strip the base from it, and finally split it on `/`s.
        $uri = $request->getUri();
        $strippedPath = ltrim(
            substr(
                $uri->getPath(),
                strlen($oldBaseUrl),
            ),
            '/',
        );
        $strippedPathParts = explode('/', $strippedPath);

        // Check if the zeroth element of the stripped path is a supported language. Note that the zeroth element is
        // always defined due to the nature of the `explode()` call above.
        //
        // Here are some example of that behaviour (note that `{baseUrl}` can be `domain/` or even `domain/path/`):
        //
        // '{baseUrl}/en'
        // array(1) {
        //     [0] => string(0) "en"
        // }
        //
        // '{baseUrl}/nl/'
        // array(1) {
        //     [0] => string(2) "nl"
        //     [1] => string(0) ""
        // }
        //
        // '{baseUrl}/en/complex/route'
        // array(3) {
        //     [0] => string(2) "en"
        //     [1] => string(7) "complex"
        //     [2] => string(5) "route"
        // }
        if (in_array($strippedPathParts[0], $languages)) {
            // The language is valid and in the URL.
            $language = $strippedPathParts[0];

            // It is not necessary to have `{baseUrl}/:language/`, as it should also be possible to use
            // `{baseUrl}/:language` (without the trailing slash).
            if (1 === count($strippedPathParts)) {
                $this->setBaseUrl($oldBaseUrl);
                // (Re)setting the path is necessary, otherwise no routes are matched.
                $uri->setPath('/');
            } else {
                // Pretend that the language is actually not part of any matchable routes by adding it to the base URL.
                $this->setBaseUrl($oldBaseUrl . '/' . $language);
            }
        } else {
            // The language was not provided through the URL, so we need to determine it based on some other factors.
            $language = $this->getLanguage($request);
        }

        // To prevent having to match the same route multiple times throughout the application we store the last matched
        // language in the router.
        $this->lastMatchedLanguage = $language;

        // We have temporarily changed the base URL to include the language. This means we can now let the
        // `TranslatorAwareTreeRouteStack` handle the remainder of the routes (as if the language does not exist). The
        // result is similar to not having this custom route stack.
        $routeMatchRemainder = parent::match($request, $pathOffset, $options);
        // If a route was found, set the language parameter such that it is accessible to the controllers.
        $routeMatchRemainder?->setParam('language', $language);

        // Finally, we set the base URL back to its original value (without language).
        $this->setBaseUrl($oldBaseUrl);

        return $routeMatchRemainder;
    }

    /**
     * Get the last matched language from a request.
     */
    public function getLastMatchedLanguage(): ?string
    {
        return $this->lastMatchedLanguage;
    }

    /**
     * Get the stored (preferred) language or try to determine it based on the request.
     */
    private function getLanguage(RequestInterface $request): string
    {
        $session = new SessionContainer('lang');

        if (isset($session->lang)) {
            return $session->lang;
        }

        // We have not stored a (preferred) language for this session, it is likely this is the first request. Try to
        // determine the preferred language using the `Accept-Language` request header if it is present.
        $lang = $this->determinePreferredLanguageFromRequest($request);

        // Store the (preferred) language in the session.
        $session->lang = $lang;

        return $session->lang;
    }

    /**
     * Determine the preferred language based on the `Accept-Language` header. If no language is the header is supported
     * we always return English as the default language.
     */
    private function determinePreferredLanguageFromRequest(RequestInterface $request): string
    {
        $header = $request->getHeader('Accept-Language');

        if ($header instanceof AcceptLanguage) {
            // Sort the languages based on preference.
            $languages = $header->getPrioritized();

            /** @var LanguageFieldValuePart $lang */
            foreach ($languages as $lang) {
                $langString = $lang->getLanguage();

                if (str_starts_with($langString, 'nl')) {
                    return 'nl';
                }

                if (str_starts_with($langString, 'en')) {
                    return 'en';
                }
            }
        }

        return 'en';
    }
}
