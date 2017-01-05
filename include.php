<?php
/*
 * File to include in external pages.
 */

define('WEB_DIR', '/var/www/gewisweb');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

$cwd = getcwd();
chdir(WEB_DIR);
$requestUri = $_SERVER['REQUEST_URI'];
$_SERVER['REQUEST_URI'] = '/external/';

// Setup autoloading
require 'init_autoloader.php';

$config = require 'config/application.config.' . APP_ENV .'.php';

// Prevent files from loading which we can't read
$config['module_listener_options']['config_glob_paths'] = ['config/autoload/{,*.}{external,global}.php'];

// Unload modules which we don't need
//$config['modules'] = array_diff($config['modules'], ['Photo', 'Activity']);

// Init the application
$application = Zend\Mvc\Application::init($config);

/*
 * We add the file which this file is included in as a template to the template map.
 * In this way the application can load this file and render it as a template.
 */
$sm = $application->getServiceManager();
$resolver = $sm->get('ViewTemplateMapResolver');
$resolver->add('application/index/external', $_SERVER['SCRIPT_FILENAME']);

// Switch back to the original directory.
chdir($cwd);

$application->run();

// Restore the request uri
$_SERVER['REQUEST_URI'] = $requestUri;

// Exit so we don't display the contents of the page twice.
exit;