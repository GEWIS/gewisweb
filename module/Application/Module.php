<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container as SessionContainer;
use Zend\Validator\AbstractValidator;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $translator = $e->getApplication()->getServiceManager()->get('translator');
        $translator->setlocale($this->determineLocale($e));

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'logError']);
        $eventManager->attach(MvCEvent::EVENT_RENDER_ERROR, [$this, 'logError']);

        // enable Zend\Validate default translator
        AbstractValidator::setDefaultTranslator($translator, 'validate');
    }

    public function logError(MvCEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();
        $logger = $sm->get('logger');

        if ($e->getError() === 'error-exception') {
            $ex = $e->getParam('exception');
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

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        if (APP_ENV === 'production') {
            return [
                'Zend\Loader\ClassMapAutoloader' => [
                    __DIR__ . '/autoload_classmap.php',
                ]
            ];
        }

        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ]
            ]
        ];
    }

    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'application_service_storage' => 'Application\Service\FileStorage',
                'application_service_legacy' => 'Application\Service\Legacy',
                'application_service_email' => 'Application\Service\Email'
            ],
            'factories' => [
                'application_get_languages' => function ($sm) {
                    return ['nl', 'en'];
                },
                'logger' => function ($sm) {
                    $logger = new Logger("gewisweb");
                    $config = $sm->get('config')['logging'];

                    $handler = new RotatingFileHandler(
                        $config['logfile_path'],
                        $config['max_rotate_file_count'],
                        $config['minimal_log_level']
                    );
                    $logger->pushHandler($handler);

                    return $logger;
                }
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
                'acl' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $helper = new \Application\View\Helper\Acl();
                    $helper->setServiceLocator($locator);
                    return $helper;
                },
                'scriptUrl' => function ($sm) {
                    $helper = new \Application\View\Helper\ScriptUrl();
                    return $helper;
                },
                'moduleIsActive' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $helper = new \Application\View\Helper\ModuleIsActive();
                    $helper->setServiceLocator($locator);
                    return $helper;
                },
                'jobCategories' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $helper = new \Application\View\Helper\JobCategories();
                    $helper->setServiceLocator($locator);
                    return $helper;
                },
                'fileUrl' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $helper = new \Application\View\Helper\FileUrl();
                    $helper->setServiceLocator($locator);
                    return $helper;
                },
                'infima' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $helper = new \Application\View\Helper\Infima();
                    $helper->setLegacyService($locator->get('application_service_legacy'));
                    return $helper;
                }
            ]
        ];
    }
}
