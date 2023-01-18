<?php
// Turn off error reporting for security reasons
error_reporting(1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('date.timezone','Europe/Berlin');
ini_set('intl.default_locale','de_DE');

$settings= [];
// Add common settings
// ...
$settings['view'] = [
    // Path to templates
    'path' => __DIR__ . '/../templates',
    // Default attributes
    'attributes' => [],
];

// Session
$settings['session'] = [
    'name' => 'dienstplan',
    'lifetime' => 7200,
    'path' => null,
    'domain' => null,
    'secure' => false,
    'httponly' => true,
    'cache_limiter' => 'nocache',
];

// Logger settings
$settings['logger'] = [
    'name' => 'app',
    'path' => __DIR__ . '/../logs',
    'filename' => 'dienstplan.log',
    'level' => \Monolog\Level::Debug,
    'file_permission' => 0775,
];

// Error Handling Middleware settings
$settings['error'] = [

    // Should be set to false in production
    'display_error_details' => true,

    // Parameter is passed to the default ErrorHandler
    // View in rendered output by enabling the "displayErrorDetails" setting.
    // For the console and unit tests we also disable it
    'log_errors' => true,

    // Display error details in error log
    'log_error_details' => true,

    // The error logfile
    'log_file' => 'error.log',
];

return $settings;
