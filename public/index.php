<?php
define('APP_ENV', getenv('APP_ENV') ?: 'production');

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Setup autoloading
if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
} else {
    throw new RuntimeException('Unable to load dependencies. Run `php composer.phar install`.');
}


// Run the application!
Laminas\Mvc\Application::init(require 'config/application.config.' . APP_ENV . '.php')->run();
