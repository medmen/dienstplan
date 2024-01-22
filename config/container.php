<?php

use Dienstplan\Support\Config;
use Dienstplan\Factory\LoggerFactory;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

return [
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    Config::class => function (ContainerInterface $container) {
        return new Config($container->get('settings'));
    },

    Messages::class => function () {
        // Don't use $_SESSION here, because the session is not started at this moment.
        // The middleware changes the storage.
        $storage = [];
        return new Messages($storage);
    },

    SessionManagerInterface::class => function (ContainerInterface $container) {
        return $container->get(SessionInterface::class);
    },

    SessionInterface::class => function (ContainerInterface $container) {
        $options = $container->get('settings')['session'];

        return new PhpSession($options);
    },

    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory($container->get('settings')['logger']);
    },

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getResponseFactory();
    },

    PhpRenderer::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['view'];

        return new PhpRenderer($settings['path'], $settings['attributes']);
    },

];
