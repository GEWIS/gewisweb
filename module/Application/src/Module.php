<?php

/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @see      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 *
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Application\Service\{
    Email as EmailService,
    FileStorage as FileStorageService,
    Infimum as InfimumService,
    WatermarkService,
};
use Application\View\Helper\{
    Acl,
    FileUrl,
    JobCategories,
    ModuleIsActive,
    ScriptUrl,
};
use Laminas\Mvc\{
    I18n\Translator as MvcTranslator,
    ModuleRouteListener,
    MvcEvent,
};
use Psr\Container\ContainerInterface;
use Laminas\Http\Header\Accept\FieldValuePart\LanguageFieldValuePart;
use Laminas\Http\Header\AcceptLanguage;
use Laminas\Http\Request;
use Laminas\Cache\Storage\Adapter\{
    Memcached,
    MemcachedOptions,
};
use Laminas\I18n\Translator\Translator as I18nTranslator;
use Laminas\Session\Container as SessionContainer;
use Laminas\Validator\AbstractValidator;
use Locale;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use RuntimeException;
use User\Permissions\NotAllowedException;

class Module
{
    /**
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e): void
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $locale = $this->determineLocale($e);

        /** @var MvcTranslator $mvcTranslator */
        $mvcTranslator = $e->getApplication()->getServiceManager()->get(MvcTranslator::class);
        $translator = $mvcTranslator->getTranslator();
        if ($translator instanceof I18nTranslator) {
            $translator->setlocale($locale);
        }

        Locale::setDefault($locale);

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'logError']);
        $eventManager->attach(MvCEvent::EVENT_RENDER_ERROR, [$this, 'logError']);

        // Enable Laminas\Validator default translator
        AbstractValidator::setDefaultTranslator($mvcTranslator);
    }

    /**
     * @param MvcEvent $e
     */
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

    /**
     * @param MvcEvent $e
     *
     * @return string
     */
    protected function determineLocale(MvcEvent $e): string
    {
        $session = new SessionContainer('lang');

        if (!isset($session->lang)) {
            // Check the preferred language in the Accept-Language request header if present
            $request = $e->getRequest();
            if ($request instanceof Request) {
                $lang = $this->getPreferedLanguageFromRequest($request);
                if (null !== $lang) {
                    $session->lang = $lang;
                }
            }
        }

        if (!isset($session->lang)) {
            // default: en locale
            $session->lang = 'en';
        }

        return $session->lang;
    }

    protected function getPreferedLanguageFromRequest(Request $request): ?string
    {
        $header = $request->getHeader('Accept-Language');
        if ($header instanceof AcceptLanguage) {
            $languages = $header->getPrioritized();
            /** @var LanguageFieldValuePart $lang */
            foreach ($languages as $lang) {
                $langString = $lang->getLanguage();
                if (str_starts_with($langString, 'nl')) {
                    return 'nl';
                } elseif (str_starts_with($langString, 'en')) {
                    return 'en';
                }
            }
        }
        return null;
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
            'factories' => [
                'application_service_email' => function (ContainerInterface $container) {
                    $renderer = $container->get('ViewRenderer');
                    $transport = $container->get('user_mail_transport');
                    $emailConfig = $container->get('config')['email'];

                    return new EmailService($renderer, $transport, $emailConfig);
                },
                'application_service_infimum' => function (ContainerInterface $container) {
                    $infimumCache = $container->get('application_cache_infimum');
                    $translator = $container->get(MvcTranslator::class);
                    $infimumConfig = $container->get('config')['infimum'];

                    return new InfimumService($infimumCache, $translator, $infimumConfig);
                },
                'application_service_storage' => function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);
                    $storageConfig = $container->get('config')['storage'];
                    $watermarkService = $container->get('application_service_watermark');

                    return new FileStorageService($translator, $storageConfig, $watermarkService);
                },
                'application_service_watermark' => function (ContainerInterface $container) {
                    $authService = $container->get('user_auth_user_service');
                    $remoteAddress = $container->get('user_remoteaddress');

                    return new WatermarkService($authService, $remoteAddress);
                },
                'application_get_languages' => function () {
                    return ['nl', 'en'];
                },
                'application_cache_infimum' => function () {
                    $cache = new Memcached();
                    // The TTL is 5 minutes (60 seconds * 5), as Supremum has a 5 minute cache on their end too. There
                    // is no need to keep requesting an infimum if we get the same one back for 5 minutes.
                    $options = $cache->getOptions();
                    if (!($options instanceof MemcachedOptions)) {
                        throw new RuntimeException('Unable to retrieve and set options for Memcached');
                    }
                    $options->setTtl(60 * 5);
                    $options->setServers(['memcached', '11211']);

                    return $cache;
                },
                'logger' => function (ContainerInterface $container) {
                    $logger = new Logger('gewisweb');
                    $config = $container->get('config')['logging'];

                    $handler = new RotatingFileHandler(
                        $config['logfile_path'],
                        $config['max_rotate_file_count'],
                        $config['minimal_log_level']
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
                'acl' => function (ContainerInterface $container) {
                    $helper = new Acl();
                    $helper->setServiceLocator($container);

                    return $helper;
                },
                'scriptUrl' => function () {
                    return new ScriptUrl();
                },
                'moduleIsActive' => function (ContainerInterface $container) {
                    $helper = new ModuleIsActive();
                    $helper->setServiceLocator($container);

                    return $helper;
                },
                'jobCategories' => function (ContainerInterface $container) {
                    $companyQueryService = $container->get('company_service_companyquery');

                    return new JobCategories($companyQueryService);
                },
                'fileUrl' => function (ContainerInterface $container) {
                    $helper = new FileUrl();
                    $helper->setServiceLocator($container);

                    return $helper;
                },
            ],
        ];
    }
}
