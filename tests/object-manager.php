<?php

declare(strict_types=1);

use App\Kernel;

require_once __DIR__ . '/bootstrap.php';

$kernel = new Kernel(
    strval($_SERVER['APP_ENV'] ?? 'test'),
    boolval($_SERVER['APP_DEBUG'] ?? false),
);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
