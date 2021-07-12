<?php
// Inherit production config
$config = require 'config/application.config.production.php';

// Enable Zend Developer Tools module
$config['modules'][] = 'Laminas\DeveloperTools';
// Enable module for generating test data
$config['modules'][] = 'TestData';

return $config;
