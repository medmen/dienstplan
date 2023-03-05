<?php
namespace Dienstplan\Action;

use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Dienstplan\Worker\Wishes;

class WishSubmitAction
{
    private SessionInterface $session;
    private SessionManagerInterface $sessionManager;

    public function __construct(SessionInterface $session, SessionManagerInterface $sessionManager,){
        $this->session = $session;
        $this->sessionManager = $sessionManager;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        $target_month = \DateTimeImmutable::createFromFormat('U', $data['target_month']);

        // Clear all flash messages
        $flash = $this->session->getFlash();
        $flash->clear();

        $wishes = new Wishes($target_month);
        $success = $wishes->save($data);

        if($success['status'] == 'error') {
            $flash->add('error', 'Fehler beim Speichern: '.$success['message']);
        } else {
            $flash->add('success', 'Erfolgreich gespeichert');
        }


        // Get RouteParser from request to generate the urls
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        // Redirect back to the login page
        $url = $routeParser->urlFor('wishes');

        return $response->withStatus(302)->withHeader('Location', $url);
    }
}
