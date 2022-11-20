<?php

use App\Support\Config;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

return [
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    Config::class => function (ContainerInterface $container) {
        return new Config($container->get('settings'));
    },

    PhpRenderer::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['view'];

        return new PhpRenderer($settings['path'], $settings['attributes']);
    },

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },
];
