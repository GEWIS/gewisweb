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

use Application\Service\Email;
use Application\Service\FileStorage;
use Application\Service\Legacy;
use Application\View\Helper\Acl;
use Application\View\Helper\FileUrl;
use Application\View\Helper\Infima;
use Application\View\Helper\JobCategories;
use Application\View\Helper\ModuleIsActive;
use Application\View\Helper\ScriptUrl;
use Carbon\Carbon;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Interop\Container\ContainerInterface;
use Laminas\Session\Container as SessionContainer;
use Laminas\Validator\AbstractValidator;
use Locale;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use User\Permissions\NotAllowedException;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $locale = $this->determineLocale($e);

        $translator = $e->getApplication()->getServiceManager()->get('translator');
        $translator->setlocale($locale);

        Carbon::setLocale($locale);
        Locale::setDefault($locale);

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'logError']);
        $eventManager->attach(MvCEvent::EVENT_RENDER_ERROR, [$this, 'logError']);

        // enable Laminas\Validate default translator
        AbstractValidator::setDefaultTranslator($translator, 'validate');
    }

    public function logError(MvCEvent $e)
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

    protected function determineLocale(MvcEvent $e)
    {
        $session = new SessionContainer('lang');
        if (!isset($session->lang)) {
            // default: nl locale
            $session->lang = 'nl';
        }

        return $session->lang;
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

    public function getServiceConfig()
    {
        return [
            'factories' => [
                'application_service_legacy' => function () {
                    return new Legacy();
                },
                'application_service_email' => function (ContainerInterface $container) {
                    $renderer = $container->get('ViewRenderer');
                    $transport = $container->get('user_mail_transport');
                    $emailConfig = $container->get('config')['email'];

                    return new Email($renderer, $transport, $emailConfig);
                },
                'application_service_storage' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $storageConfig = $container->get('config')['storage'];

                    return new FileStorage($translator, $storageConfig);
                },
                'application_get_languages' => function () {
                    return ['nl', 'en'];
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
    public function getViewHelperConfig()
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
                'infima' => function (ContainerInterface $container) {
                    $helper = new Infima();
                    $helper->setLegacyService($container->get('application_service_legacy'));

                    return $helper;
                },
            ],
        ];
    }
}
