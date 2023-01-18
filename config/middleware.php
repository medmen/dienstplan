<?php
use Odan\Session\Middleware\SessionStartMiddleware;
use Slim\App;

return function (App $app) {

    // Start the session
    $app->add(SessionStartMiddleware::class);

    // Add the Slim built-in routing middleware
    $app->addRoutingMiddleware();

    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();

    // Handle exceptions
    $app->addErrorMiddleware(true, true, true);
    // $app->add(ErrorMiddleware::class);
};

