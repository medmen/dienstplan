<?php
namespace Dienstplan\Action\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Views\PhpRenderer;
use Odan\Session\SessionInterface;

final class LoginAction
{
    private PhpRenderer $renderer;
    private Sessioninterface $session;

    public function __construct(PhpRenderer $renderer, SessionInterface $session)
    {
        $this->renderer = $renderer;
        $this->session = $session;
    }

    public function __invoke(
        Request $request,
        Response $response
    ): Response {
        // Get RouteParser from request to generate the urls
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        if($this->session->get('user') !== null) {
            $this->session->getFlash()->add('info', 'Sie sind bereits eingeloggt!');
            // Redirect back to the login page
            $url = $routeParser->urlFor('home');
            return $response->withStatus(302)->withHeader('Location', $url);
        }
        return $this->renderer->render($response, 't_login.php', ['method' => 'post', 'action' => '/login', 'title'=> 'anmelden']);
    }
}
