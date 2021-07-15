<?php
/*
 * File to include in external pages.
 */

use Laminas\Mvc\MvcEvent;

define('WEB_DIR', '/var/www/gewisweb');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

$cwd = getcwd();
chdir(WEB_DIR);
$requestUri = $_SERVER['REQUEST_URI'];
$_SERVER['REQUEST_URI'] = '/external/';

// Setup autoloading
if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
} else {
    throw new RuntimeException('Unable to load dependencies. Run `php composer.phar install`.');
}

// Config
$config = include 'config/application.config.php';
if (APP_ENV === 'development' && file_exists('config/development.config.php')) {
    $config = Laminas\Stdlib\ArrayUtils::merge($config, include 'config/development.config.php');
}

// Prevent files from loading which we can't read
$config['module_listener_options']['config_glob_paths'] = ['config/autoload/{,*.}{external,global}.php'];

// Unload modules which we don't need
//$config['modules'] = array_diff($config['modules'], ['Photo', 'Activity']);

// Init the application
$application = Laminas\Mvc\Application::init($config);

/*
 * We add the file which this file is included in as a template to the template map.
 * In this way the application can load this file and render it as a template.
 */
$sm = $application->getServiceManager();
$resolver = $sm->get('ViewTemplateMapResolver');
$resolver->add('application/index/external', $_SERVER['SCRIPT_FILENAME']);

$eventManager = $application->getEventManager();

$router = $sm->get('Router');

// Add an route for this external page
$router->addRoute('external', [
    'type' => 'Segment',
    'options' => [
        'route' => $_SERVER['REQUEST_URI'],
        'defaults' => [
            '__NAMESPACE__' => 'Application\Controller',
            'controller'    => 'Index',
            'action'        => 'external',
        ]
    ],
    'priority' => 100
]);

$eventManager->attach (MvcEvent::EVENT_ROUTE, function (MvcEvent $e) {
    global $requestUri;
    // Restore the request uri just in time before it gets used
    $_SERVER['REQUEST_URI'] = $requestUri;
});

// Switch back to the original directory.
chdir($cwd);

// Nothing to see here, just a regular zf2 application...
$application->run();


// Exit so we don't display the contents of the page twice.
exit;
