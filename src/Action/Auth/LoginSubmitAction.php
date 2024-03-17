<?php

namespace Dienstplan\Action\Auth;

use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Dienstplan\Support\Config;


final class LoginSubmitAction
{
    private SessionInterface $session;
    private SessionManagerInterface $sessionManager;

    public function __construct(
        SessionInterface $session,
        SessionManagerInterface $sessionManager,
        Config $config
    ) {
        $this->session = $session;
        $this->sessionManager = $sessionManager;
        $this->config = $config;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $data = (array)$request->getParsedBody();
        $username = (string)($data['username'] ?? '');
        $password = (string)($data['password'] ?? '');

        // Pseudo example
        // Check user credentials.
        // You may use an application/domain service and the database here.
        $user = null;
        if ($username === 'admin' && $password === 'secret') {
            $user = 'admin';
        }

        $path_to_people = $this->config->get('people');
        $people = require_once($path_to_people);

        if (in_array($username, $people)) {
           if(password_hash($password) === $people[$username]['pw']) {
               $user = $username;
               $is_admin = $people[$username]['is_admin'] ?? false;
           }
        }


        // Clear all flash messages
        $flash = $this->session->getFlash();
        $flash->clear();

        // Get RouteParser from request to generate the urls
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        if ($user) {
            // Login successfully
            // Clears all session data and regenerate session ID
            $this->sessionManager->destroy();
            $this->sessionManager->start();
            $this->sessionManager->regenerateId();

            $this->session->set('user', $user);
            if(isset($is_admin) and $is_admin === true) {
                $this->session->set('is_admin', true);
            }
            $flash->add('success', 'Login successfully');

            // Redirect to protected page
            $url = $routeParser->urlFor('users');
        } else {
            $flash->add('error', 'Login failed!');

            // Redirect back to the login page
            $url = $routeParser->urlFor('login');
        }

        return $response->withStatus(302)->withHeader('Location', $url);
    }
}
