<?php
// Turn off error reporting for security reasons
error_reporting(1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$settings= [];
// Add common settings
// ...
$settings['view'] = [
    // Path to templates
    'path' => __DIR__ . '/../templates',
    // Default attributes
    'attributes' => [],
];


return $settings;
