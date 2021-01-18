<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

/**
 * This autoloading setup is really more complicated than it needs to be for most
 * applications. The added complexity is simply to reduce the time it takes for
 * new developers to be productive with a fresh skeleton. It allows autoloading
 * to be correctly configured, regardless of the installation method and keeps
 * the use of composer completely optional. This setup should work fine for
 * most users, however, feel free to configure autoloading however you'd like.
 */

if (APP_ENV === 'production') {
    require_once 'vendor/zendframework/zendframework/library/Zend/Loader/AutoloaderFactory.php';
    require_once 'vendor/zendframework/zendframework/library/Zend/Loader/ClassMapAutoloader.php';
    if (!file_exists('vendor/composer/autoload_classmap.php')) {
        throw new RuntimeException('Unable to load vendor classmap. Run `php composer.phar install -o`.');
    }

    Zend\Loader\AutoloaderFactory::factory([
        'Zend\Loader\ClassMapAutoloader' => [
            'Composer' => 'vendor/composer/autoload_classmap.php',
        ]
    ]);
} else {
    // Composer autoloading
    if (file_exists('vendor/autoload.php')) {
        $loader = include 'vendor/autoload.php';
    }
}

if (!class_exists('Zend\Loader\AutoloaderFactory')) {
    throw new RuntimeException('Unable to load dependencies. Run `php composer.phar install`.');
}

