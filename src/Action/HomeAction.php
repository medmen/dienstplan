<?php
namespace App\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Support\Config;

final class HomeAction
{
    private $persons;

    public function __construct(Config $config)
    {
        // Read settings
        $this->persons = serialize($config->get("people"));

        // Reading a key using the dot notation
        // echo $config->get('db.hostname') . "\n";
    }


    public function __invoke(Request $request, Response $response): Response
    {
        $response->getBody()->write('Hello, World! <br>'.$this->persons);
        return $response;
        /**
        $response->getBody()->write(json_encode(['hello' => 'world']));
        return $response->withHeader('Content-Type', 'application/json');
         */
    }
}
