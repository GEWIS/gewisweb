<?php

$appEnv = getenv('APP_ENV');
define('APP_ENV', false !== $appEnv ? $appEnv : 'production');

// `NONCE_REPLACEMENT_STRING` is required for production, if not set we should not continue loading the application.
if (APP_ENV === 'production') {
    if (false === getenv('NONCE_REPLACEMENT_STRING')) {
        throw new RuntimeException(
            "Could not find `NONCE_REPLACEMENT_STRING`.\n"
        );
    }
} else {
    // This is necessary, otherwise the open redirect protection does not work locally.
    if (isset($_SERVER['SERVER_PORT'])) {
        unset($_SERVER['SERVER_PORT']);
    }
}

$nonceReplacementString = getenv('NONCE_REPLACEMENT_STRING');
define('NONCE_REPLACEMENT_STRING', false !== $nonceReplacementString ? $nonceReplacementString : '');

// make sure we are in the correct directory
chdir(__DIR__);

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
    /**
     * @return array
     */
    public static function getConfig(): array
    {
        // Retrieve configuration
        $appConfig = require __DIR__ . '/config/application.config.php';
        if ('production' !== APP_ENV && file_exists(__DIR__ . '/config/development.config.php')) {
            $appConfig = ArrayUtils::merge($appConfig, require __DIR__ . '/config/development.config.php');
        }

        return $appConfig;
    }

    /**
     * @return Application
     */
    public static function getApplication(): Application
    {
        // Retrieve configuration
        $appConfig = self::getConfig();

        // Initialise the application!
        return Application::init($appConfig);
    }

    /**
     * @return ServiceManager
     */
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
