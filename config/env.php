<?php

// Secret

return function (array $settings): array {

    $settings['people'] = __DIR__.'/../data/people.php';

    $settings['view'] = [
        // Path to templates
        'path' => __DIR__ . '/../templates',
        // Default attributes
        'attributes' => [],
    ];

    return $settings;
};
