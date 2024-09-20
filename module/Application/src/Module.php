<?php

declare(strict_types=1);

namespace Application;

use Application\Command\Factory\LoadFixturesFactory as LoadFixturesCommandFactory;
use Application\Command\LoadFixtures as LoadFixturesCommand;
use Application\Router\Factory\LanguageAwareTreeRouteStackFactory;
use Application\Router\LanguageAwareTreeRouteStack;
use Application\Service\Email as EmailService;
use Application\Service\Factory\EmailFactory as EmailServiceFactory;
use Application\Service\Factory\FileStorageFactory as FileStorageServiceFactory;
use Application\Service\Factory\InfimumFactory as InfimumServiceFactory;
use Application\Service\Factory\WatermarkFactory;
use Application\Service\FileStorage as FileStorageService;
use Application\Service\Infimum as InfimumService;
use Application\Service\Watermark;
use Exception;
use Laminas\Cache\Storage\Adapter\Memcached;
use Laminas\Cache\Storage\Adapter\MemcachedOptions;
use Laminas\I18n\Translator\Translator as I18nTranslator;
use Laminas\Mvc\Application as MvcApplication;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteStackInterface;
use Laminas\Validator\AbstractValidator;
use League\Glide\Signatures\Signature;
use League\Glide\Urls\UrlBuilder;
use Locale;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Override;
use Psr\Container\ContainerInterface;
use RuntimeException;
use User\Permissions\NotAllowedException;

use function array_merge;
use function hash;
use function http_build_query;
use function ksort;
use function ltrim;

class Module
{
    public function onBootstrap(MvcEvent $e): void
    {
        $application = $e->getApplication();
        $serviceManager = $application->getServiceManager();
        $eventManager = $application->getEventManager();
        // TODO: test if the following is truly necessary
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        // Attach listener for locale determination through the `LanguageAwareTreeRouteStack`.
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], 100);

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'logError']);
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'logError']);

        // Enable Laminas\Validator default translator
        /**
         * @psalm-suppress UnnecessaryVarAnnotation
         * @var MvcTranslator $mvcTranslator
         */
        $mvcTranslator = $serviceManager->get(MvcTranslator::class);
        AbstractValidator::setDefaultTranslator($mvcTranslator);
    }

    public function logError(MvcEvent $e): void
    {
        $container = $e->getApplication()->getServiceManager();
        $logger = $container->get('logger');

        if (MvcApplication::ERROR_ROUTER_NO_MATCH === $e->getError()) {
            // not an interesting error
            return;
        }

        if (MvcApplication::ERROR_EXCEPTION === $e->getError()) {
            $ex = $e->getParam('exception');

            if ($ex instanceof NotAllowedException) {
                // we do not need to log access denied
                return;
            }

            $logger->error($ex);

            return;
        }

        $logger->error($e->getError());
    }

    public function onRoute(MvcEvent $e): void
    {
        $serviceManager = $e->getApplication()->getServiceManager();
        /** @var RouteStackInterface $router */
        $router = $serviceManager->get('router');

        if (!$router instanceof LanguageAwareTreeRouteStack) {
            return;
        }

        // Check whether the router has already performed a match, if this is the case we do not have to match again.
        $language = $router->getLastMatchedLanguage();

        if (null === $language) {
            // Router has not performed a match yet, this is weird. However, we match the route to obtain the language.
            // Even with a 404 (i.e. not matching route) we will obtain a language (thus guaranteed to be not `null`).
            $router->match($serviceManager->get('request'));

            /** @var string $language */
            $language = $router->getLastMatchedLanguage();
        }

        $mvcTranslator = $serviceManager->get(MvcTranslator::class);
        $translator = $mvcTranslator->getTranslator();

        if ($translator instanceof I18nTranslator) {
            $translator->setlocale($language);
        }

        Locale::setDefault($language);
    }

    /**
     * Get the configuration for this module.
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig(): array
    {
        return [
            'delegators' => [
                'HttpRouter' => [LanguageAwareTreeRouteStackFactory::class],
                TreeRouteStack::class => [LanguageAwareTreeRouteStackFactory::class],
            ],
            'factories' => [
                EmailService::class => EmailServiceFactory::class,
                InfimumService::class => InfimumServiceFactory::class,
                FileStorageService::class => FileStorageServiceFactory::class,
                Watermark::class => WatermarkFactory::class,
                'application_cache_infimum' => static function () {
                    $cache = new Memcached();
                    $options = $cache->getOptions();

                    if (!($options instanceof MemcachedOptions)) {
                        throw new RuntimeException('Unable to retrieve and set options for Memcached');
                    }

                    // The TTL is 5 minutes (60 seconds * 5), as Supremum has a 5-minute cache on their end too. There
                    // is no need to keep requesting an infimum if we get the same one back for 5 minutes.
                    $options->setTtl(60 * 5);
                    $options->setServers([
                        [
                            'host' => 'memcached',
                            'port' => 11211,
                        ],
                    ]);
                    $options->setNamespace('Infima');

                    return $cache;
                },
                'logger' => static function (ContainerInterface $container) {
                    $logger = new Logger('gewisweb');
                    $config = $container->get('config')['logging'];

                    $handler = new RotatingFileHandler(
                        $config['logfile_path'],
                        $config['max_rotate_file_count'],
                        $config['minimal_log_level'],
                    );
                    $logger->pushHandler($handler);

                    return $logger;
                },
                'glide_url_builder' => static function (ContainerInterface $container) {
                    $config = $container->get('config');

                    if (
                        !isset($config['glide']) || !isset($config['glide']['base_url'])
                        || !isset($config['glide']['signing_key'])
                    ) {
                        throw new Exception('Invalid glide configuration');
                    }

                    // Custom implementation of Signature to enable usage of SHA3-256
                    $signature = new class ($config['glide']['signing_key']) extends Signature {
                        /**
                         * @inheritDoc
                         */
                        #[Override]
                        public function addSignature(
                            $path,
                            array $params,
                        ): array {
                            return array_merge($params, ['s' => $this->generateSignature($path, $params)]);
                        }

                        /**
                         * IMPORTANT: This function MUST be exactly the same as the one used in the Glide server.
                         *
                         * @inheritDoc
                         */
                        #[Override]
                        public function generateSignature(
                            $path,
                            array $params,
                        ) {
                            unset($params['s']);
                            ksort($params);

                            // MODIFIED: use SHA3-256 instead of md5 for better guarantees the signature is not crafted.
                            return hash(
                                'sha3-256',
                                $this->signKey . ':' . ltrim($path, '/') . '?' . http_build_query($params),
                            );
                        }
                    };

                    return new UrlBuilder($config['glide']['base_url'], $signature);
                },
                LoadFixturesCommand::class => LoadFixturesCommandFactory::class,
            ],
        ];
    }
}
