<?php
define('APP_ENV', getenv('APP_ENV') ?: 'production');

date_default_timezone_set('Europe/Amsterdam');

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Setup autoloading
require 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.' . APP_ENV .'.php')->run();
