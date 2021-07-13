<?php
// make sure we are in the correct directory
use Doctrine\ORM\EntityManager;

chdir(__DIR__);

require 'bootstrap.php';

$application = ConsoleRunner::getApplication();

return $application->bootstrap()->getServiceManager()->get(EntityManager::class);
