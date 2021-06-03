<?php
//const APP_ENV = 'development';
$env = getenv('APP_ENV') ?: 'production';

// Inherit production config
$config = require 'config/application.config.production.php';

// Enable Zend Developer Tools module
$config['modules'][] = 'ZendDeveloperTools';
// Enable module for generating test data
$config['modules'][] = 'TestData';

// Whether or not to enable modules dependency checking.
// Enabled by default, prevents usage of modules that depend on other modules
// that weren't loaded.
$config['check_dependencies'] = false;

return $config;
