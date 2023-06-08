<?php

namespace Dienstplan\Action\User;

use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserAction
{
    private SessionInterface $session;
    private $flash;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
        $this->flash = $session->getFlash();
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $user = $this->session->get('user');
        $all_flash = json_encode($this->flash->all());
        $response->getBody()->write(sprintf('Welcome %s', $user).' - '.sprintf('FLASH: %s', $all_flash) );

        return $response;
    }
}
