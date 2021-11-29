<?php

define('APP_ENV', getenv('APP_ENV') ?: 'production');

// make sure we are in the correct directory
chdir(__DIR__);

use Laminas\ModuleManager\ModuleManagerInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;

// Composer autoloading
include __DIR__ . '/vendor/autoload.php';

if (!class_exists(Application::class)) {
    throw new RuntimeException(
        "Unable to load application using composer autoloading.\n"
    );
}

class ConsoleRunner
{
    public static function getConfig(): array
    {
        // Retrieve configuration
        $appConfig = require __DIR__ . '/config/application.config.php';
        if (APP_ENV === 'development' && file_exists(__DIR__ . '/config/development.config.php')) {
            $appConfig = ArrayUtils::merge($appConfig, require __DIR__ . '/config/development.config.php');
        }

        return $appConfig;
    }
    public static function getApplication(): Application
    {
        // Retrieve configuration
        $appConfig = self::getConfig();

        // Initialise the application!
        return Application::init($appConfig);
    }

    public static function getServiceManager(): ServiceManager
    {
        $appConfig = self::getConfig();

        $servicesConfig = $appConfig['service_manager'] ?? [];

        $smConfig = new ServiceManagerConfig($servicesConfig);

        $serviceManager = new ServiceManager();
        $smConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('ApplicationConfig', $appConfig);

        $moduleManager = $serviceManager->get('ModuleManager');
        $moduleManager->loadModules();

        return $serviceManager;
    }
}
