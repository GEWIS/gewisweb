<?php

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

require 'vendor/autoload.php';

use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureException;

// Setup Glide server
$server = League\Glide\ServerFactory::create([
    'source' => '/code/public/data',
    'cache' => '/glide/cache',
    'driver' => 'imagick'
]);

// set complicated sign key
$signkey = getenv('GLIDE_KEY');

$base = '';
$path = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

try {
    // Validate HTTP signature
    SignatureFactory::create($signkey)->validateRequest($base . $path, $_GET);
} catch (SignatureException $e) {
    http_response_code(403);
    die('Forbidden');
}

$server->outputImage($path, $_GET);
