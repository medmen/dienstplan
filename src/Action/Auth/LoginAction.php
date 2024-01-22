<?php
namespace Dienstplan\Action\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
        if($this->session->get('user') !== null) {
            $html = '<h2>bereits eingeloggt</h2> <section>Sie sind bereits eingeloggt!</section>';
            $response->getBody()->write($html);
            return $response;
        }
        return $this->renderer->render($response, 't_login.php', ['method' => 'post', 'action' => '/login', 'title'=> 'anmelden']);
    }
}
