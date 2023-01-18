<?php

namespace Dienstplan\Action\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Odan\Session\SessionManagerInterface;

final class LogoutAction
{
    private SessionManagerInterface $sessionManager;

    public function __construct(SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Logout user
        $this->sessionManager->destroy();

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $url = $routeParser->urlFor('logout');

        return $response->withStatus(302)->withHeader('Location', $url);
    }
}
