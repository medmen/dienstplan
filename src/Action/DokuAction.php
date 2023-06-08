<?php
namespace Dienstplan\Action;

use Dienstplan\Support\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use Odan\Session\SessionInterface;

final class DokuAction
{
    private PhpRenderer $renderer;

    private $session;

    public function __construct(Config $config, PhpRenderer $renderer, SessionInterface $session)
    {
        // Read settings
        $this->renderer = $renderer;
        $this->session = $session;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $flash = $this->session->getFlash();
        $flash->add('info', 'Invoking Doku Action');
        $this->renderer->addAttribute('user', $this->session->get('user'));

        return $this->renderer->render($response, 't_doku.php', ['year' => '2023', 'title' => 'Dokumentation']);
    }
}
