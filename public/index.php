<?php
define('APP_ENV', getenv('APP_ENV') ?: 'production');

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// For Docker:
if (file_exists('/code')) {
    chdir('/code');
}

// Setup autoloading
require 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.' . APP_ENV .'.php')->run();
