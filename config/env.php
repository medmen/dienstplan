<?php

// Secret

return function (array $settings): array {
    $settings['people'] = [
        'anton' => ['fullname' => 'Anton Anders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6'], // pw chaf666
        'berta' => ['fullname' => 'Berta Besonders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6', 'is_admin' => true],
        'conny',
        'dick',
        'egon',
        'floppy',
        'guste',
    ];

    $settings['view'] = [
        // Path to templates
        'path' => __DIR__ . '/../templates',
        // Default attributes
        'attributes' => [],
    ];

    // Docker example
    // if (isset($_ENV['DOCKER'])) {
    //    $settings['db']['host'] = $_ENV['MYSQL_HOST'] ?? 'host.docker.internal';
    //    $settings['db']['port'] = $_ENV['MYSQL_PORT'] ?? '3306';
    //    $settings['db']['username'] = $_ENV['MYSQL_USER'] ?? 'root';
    //    $settings['db']['password'] = $_ENV['MYSQL_PASSWORD'] ?? '';
    // }

    return $settings;
};
