<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Odan\Session\Middleware\SessionStartMiddleware;
use Dienstplan\Middleware\UserAuthMiddleware;
use Slim\Routing\RouteCollectorProxy;
use Slim\App;


return function (App $app) {
    // use session and flash for all routes
    $app->add(SessionStartMiddleware::class);

    // Password protected area
    $app->group('/users', function (RouteCollectorProxy $group) {
        $group->get('/', \Dienstplan\Action\User\UserAction::class)->setName('users');
        // add more routes ...
    })->add(UserAuthMiddleware::class);

    // routes definition
    // --- login related stuff ---
    $app->get('/users', \Dienstplan\Action\User\UserAction::class)->setName('users');
    $app->get('/login', \Dienstplan\Action\Auth\LoginAction::class)->setName('login');
    $app->post('/login', \Dienstplan\Action\Auth\LoginSubmitAction::class);
    $app->get('/logout', \Dienstplan\Action\Auth\LogoutAction::class)->setName('logout');
    // --- normal routes ---
    $app->get('/wuensche', \Dienstplan\Action\WishAction::class)->setName('wishes');
    $app->get('/[{target_month}]', \Dienstplan\Action\HomeAction::class)->setName('home');

/**
    $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
        $response->getBody()->write('Hello, World!');

        return $response;
    });
*/
};

