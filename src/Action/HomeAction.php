<?php
namespace Dienstplan\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use Dienstplan\Support\Config;
use Dienstplan\Worker\Dutyroster;

final class HomeAction
{
    private PhpRenderer $renderer;

    private $persons, $month, $dienstplan;

    public function __construct(Config $config, PhpRenderer $renderer)
    {
        // Read settings
        $this->persons = serialize($config->get("people"));
        $this->renderer = $renderer;

        $this->month = date('MM/YY');
        // Reading a key using the dot notation
        // echo $config->get('db.hostname') . "\n";
    }


    public function __invoke(Request $request, Response $response): Response
    {
        // if no month was given, use actual month
        if($request->getAttribute('target_month') !== null) {
            $this->month = $request->getAttribute('target_month');
        }

        $this->renderer->addAttribute('title', 'Dienstplan');
        $this->renderer->addAttribute('persons', $this->persons);

        $dutyroster = new Dutyroster();

        $this->dienstplan = $dutyroster->create_or_show_for_month($this->month);
        $this->renderer->addAttribute('dienstplan', $this->dienstplan);

        return $this->renderer->render($response, 'home.php', ['name' => 'World']);

        /**
        $response->getBody()->write(json_encode(['hello' => 'world']));
        return $response->withHeader('Content-Type', 'application/json');
         */
    }
}
