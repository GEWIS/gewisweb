<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_string($path) && __FILE__ !== $path && is_file($path)) {
        return false;
    }
    unset($path);
}

require 'bootstrap.php';

// Certain errors and exceptions cannot be caught by the Laminas exception handler (such as PDOExceptions), as a result
// a blank page is shown.
try {
    $application = ConsoleRunner::getApplication();
    $application->run();
} catch (Throwable $e) {
    // Only show the global exception page if we are not in development mode.
    if ('development' !== APP_ENV) {
        // Make sure that we actually log the problem.
        error_log('Fatal ' . $e->__toString());

        // Output the exception page (and force status code to 500, otherwise it will be 200).
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        readfile('/code/public/errors/exception.html');
    }
}
