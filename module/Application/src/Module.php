<?php

declare(strict_types=1);

namespace Application;

use Application\Router\Factory\LanguageAwareTreeRouteStackFactory;
use Application\Router\LanguageAwareTreeRouteStack;
use Application\Service\Email as EmailService;
use Application\Service\FileStorage as FileStorageService;
use Application\Service\Infimum as InfimumService;
use Application\Service\WatermarkService;
use Application\View\Helper\Acl;
use Application\View\Helper\Diff;
use Application\View\Helper\FileUrl;
use Application\View\Helper\JobCategories;
use Application\View\Helper\Markdown;
use Application\View\Helper\ModuleIsActive;
use Application\View\Helper\ScriptUrl;
use Laminas\Cache\Storage\Adapter\Memcached;
use Laminas\Cache\Storage\Adapter\MemcachedOptions;
use Laminas\I18n\Translator\Translator as I18nTranslator;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteStackInterface;
use Laminas\Validator\AbstractValidator;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Locale;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use RuntimeException;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\AuthenticationService;
use User\Authentication\Storage\UserSession;
use User\Permissions\NotAllowedException;

class Module
{
    public function onBootstrap(MvcEvent $e): void
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        // Attach listener for locale determination through the `LanguageAwareTreeRouteStack`.
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute']);

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'logError']);
        $eventManager->attach(MvCEvent::EVENT_RENDER_ERROR, [$this, 'logError']);

        // Enable Laminas\Validator default translator
        /**
         * @psalm-suppress UnnecessaryVarAnnotation
         * @var MvcTranslator $mvcTranslator
         */
        $mvcTranslator = $e->getApplication()->getServiceManager()->get(MvcTranslator::class);
        AbstractValidator::setDefaultTranslator($mvcTranslator);
    }

    public function logError(MvCEvent $e): void
    {
        $container = $e->getApplication()->getServiceManager();
        $logger = $container->get('logger');

        if ('error-router-no-match' === $e->getError()) {
            // not an interesting error
            return;
        }

        if ('error-exception' === $e->getError()) {
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
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * @return array
     */
    public function getServiceConfig(): array
    {
        return [
            'delegators' => [
                'HttpRouter' => [LanguageAwareTreeRouteStackFactory::class],
                TreeRouteStack::class => [LanguageAwareTreeRouteStackFactory::class],
            ],
            'factories' => [
                'application_service_email' => static function (ContainerInterface $container) {
                    $renderer = $container->get('ViewRenderer');
                    $transport = $container->get('user_mail_transport');
                    $emailConfig = $container->get('config')['email'];

                    return new EmailService($renderer, $transport, $emailConfig);
                },
                'application_service_infimum' => static function (ContainerInterface $container) {
                    $infimumCache = $container->get('application_cache_infimum');
                    $translator = $container->get(MvcTranslator::class);
                    $infimumConfig = $container->get('config')['infimum'];

                    return new InfimumService($infimumCache, $translator, $infimumConfig);
                },
                'application_service_storage' => static function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);
                    $storageConfig = $container->get('config')['storage'];
                    $watermarkService = $container->get('application_service_watermark');

                    return new FileStorageService($translator, $storageConfig, $watermarkService);
                },
                'application_service_watermark' => static function (ContainerInterface $container) {
                    /** @var AuthenticationService<UserSession, UserAdapter> $authService */
                    $authService = $container->get('user_auth_user_service');
                    $remoteAddress = $container->get('user_remoteaddress');

                    return new WatermarkService($authService, $remoteAddress);
                },
                'application_get_languages' => static function () {
                    return ['nl', 'en'];
                },
                'application_cache_infimum' => static function () {
                    $cache = new Memcached();
                    $options = $cache->getOptions();

                    if (!($options instanceof MemcachedOptions)) {
                        throw new RuntimeException('Unable to retrieve and set options for Memcached');
                    }

                    // The TTL is 5 minutes (60 seconds * 5), as Supremum has a 5 minute cache on their end too. There
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
            ],
        ];
    }

    /**
     * Get view helper configuration.
     *
     * @return array
     */
    public function getViewHelperConfig(): array
    {
        return [
            'factories' => [
                'acl' => static function (ContainerInterface $container) {
                    $helper = new Acl();
                    $helper->setServiceLocator($container);

                    return $helper;
                },
                'scriptUrl' => static function () {
                    return new ScriptUrl();
                },
                'moduleIsActive' => static function (ContainerInterface $container) {
                    $helper = new ModuleIsActive();
                    $helper->setServiceLocator($container);

                    return $helper;
                },
                'jobCategories' => static function (ContainerInterface $container) {
                    $companyQueryService = $container->get('company_service_companyquery');

                    return new JobCategories($companyQueryService);
                },
                'fileUrl' => static function (ContainerInterface $container) {
                    $helper = new FileUrl();
                    $helper->setServiceLocator($container);

                    return $helper;
                },
                'diff' => static function (ContainerInterface $container) {
                    return new Diff($container->get('config')['php-diff']);
                },
                'markdown' => static function (ContainerInterface $container) {
                    $environment = new Environment($container->get('config')['commonmark']);
                    $environment->addExtension(new CommonMarkCoreExtension())
                        ->addExtension(new GithubFlavoredMarkdownExtension())
                        ->addExtension(new ExternalLinkExtension());

                    return new Markdown(
                        $container->get(MvcTranslator::class),
                        new MarkdownConverter($environment),
                    );
                },
            ],
        ];
    }
}
