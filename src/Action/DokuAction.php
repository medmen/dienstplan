<?php
namespace Dienstplan\Action;

use Odan\Session\FlashInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use Odan\Session\SessionInterface;

final class DokuAction
{
    private PhpRenderer $renderer;
    private SessionInterface $session;
    private FlashInterface $flash;

    public function __construct(PhpRenderer $renderer, SessionInterface $session)
    {
        // Read settings
        $this->renderer = $renderer;
        $this->session = $session;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $this->flash = $this->session->getFlash();
        $this->flash->add('info', 'Invoking Doku Action');
        $this->renderer->addAttribute('user', $this->session->get('user'));

        return $this->renderer->render($response, 't_doku.php', ['year' => '2023', 'title' => 'Dokumentation']);
    }
}
