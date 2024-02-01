<?php
namespace Dienstplan\Action;

use Dienstplan\Worker\Holidays;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

class HolidaySubmitAction
{
    public function __construct(
        SessionInterface $session
    ){
        $this->session = $session;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        $target_month = \DateTimeImmutable::createFromFormat('U', $data['target_month']); // Date gets passed as Unix time!
        $this->flash = $this->session->getFlash();
        // Clear all flash messages
        // $this->flash->clear();
        $this->flash->add('info', 'Invoking Holiday Submit Action');

        $holidays = new Holidays($this->session);

        try {
            $holidays->save($target_month, $data);
            $this->flash->add('success', 'Urlaub erfolgreich gespeichert');
        } catch(\Exception $e) {
            $this->flash->add('error', 'Fehler beim Urlaub speichern: '.$e->getMessage());
        }

        // Get RouteParser from request to generate the urls
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        // Redirect back to the wish page
        $url = $routeParser->urlFor('holidays');

        return $response->withStatus(302)->withHeader('Location', $url);
    }
}
