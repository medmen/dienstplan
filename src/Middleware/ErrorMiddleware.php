<?php
namespace Dienstplan\Middleware;

use Psr\Container\ContainerInterface;
use Slim\Middleware\ErrorMiddleware;

return [
    // ...
    ErrorMiddleware::class => function (ContainerInterface $container) {
        $app = $container->get(App::class);
        $settings = $container->get('settings')['error'];

        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$settings['display_error_details'],
            (bool)$settings['log_errors'],
            (bool)$settings['log_error_details']
        );

        return $errorMiddleware;
    },

];
