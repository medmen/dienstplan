<?php

namespace Dienstplan\Action\User;

use Odan\Session\FlashInterface;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function DI\string;

final class UserAction
{
    private SessionInterface $session;
    private FlashInterface $flash;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
        $this->flash = $session->getFlash();
        $this->flash->clear(); // clear flash messages
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $user = $this->session->get('user');
        $all_flash = json_encode($this->flash->all());
        $response->getBody()->write(sprintf('Welcome %s', (string)$user).' - '.sprintf('FLASH: %s', (string)$all_flash) );

        return $response;
    }
}
