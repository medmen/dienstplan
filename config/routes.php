<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app) {
    $app->get('/', \Dienstplan\Action\HomeAction::class);
/**
    $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
        $response->getBody()->write('Hello, World!');

        return $response;
    });
*/
};

