<?php
namespace Dienstplan\Action;

use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Dienstplan\Worker\Wishes;

class WishSubmitAction
{
    public function __construct(
        SessionInterface $session
    ){
        $this->session = $session;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        $target_month = \DateTimeImmutable::createFromFormat('U', $data['target_month']);

        // Clear all flash messages
        $this->flash = $this->session->getFlash();
        // $this->flash->clear();

        $wishes = new Wishes($this->session, $target_month);

        try {
            $wishes->save($target_month, $data);
            $this->flash->add('success', 'Erfolgreich gespeichert');
        } catch(\Exception $e) {
            $this->flash->add('error', 'Fehler beim Speichern: '.$e->getMessage());
        }

        // Get RouteParser from request to generate the urls
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        // Redirect back to the wish page
        $url = $routeParser->urlFor('wishes');

        return $response->withStatus(302)->withHeader('Location', $url);
    }
}
