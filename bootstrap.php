<?php
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// make sure we are in the correct directory
chdir(__DIR__);

use Laminas\Mvc\Application;
use Laminas\Stdlib\ArrayUtils;

class ConsoleRunner
{
    public static function getApplication()
    {
        // Composer autoloading

        include __DIR__ . '/vendor/autoload.php';

        if (!class_exists(Application::class)) {
            throw new RuntimeException(
                "Unable to load application using composer autoloading.\n"
            );
        }

        // Retrieve configuration
        $appConfig = require __DIR__ . '/config/application.config.php';
        if (APP_ENV === 'development' && file_exists(__DIR__ . '/config/development.config.php')) {
            $appConfig = ArrayUtils::merge($appConfig, require __DIR__ . '/config/development.config.php');
        }

        // Run the application!
        return Application::init($appConfig);
    }
}
