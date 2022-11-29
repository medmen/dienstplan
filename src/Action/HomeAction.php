<?php
namespace Dienstplan\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use Dienstplan\Support\Config;
use Dienstplan\Worker\Dutyroster;
use DateTimeImmutable;
use DateInterval;

final class HomeAction
{
    private PhpRenderer $renderer;

    private $persons, $month, $dienstplan;

    public function __construct(Config $config, PhpRenderer $renderer)
    {
        // Read settings
        $this->persons = serialize($config->get("people"));
        $this->renderer = $renderer;
        $this->month = new DateTimeImmutable('now');
    }


    public function __invoke(Request $request, Response $response, array $args): Response
    {
        // if no month was given, use actual month
        $haeh = $args['target_month'];
        $month_given = $request->getQueryParams()['target_month'];

        if(is_null($month_given) === false) {
            // sanity check: make sure date given is between -10 and + 10 years from now
            $check_month = DateTimeImmutable::createFromFormat('m/Y', $month_given);

            $nowplus10y = $this->month->add(new DateInterval('P10Y'));
            $nowminus10y = $this->month->sub(new DateInterval('P10Y'));

            if($nowminus10y < $check_month and $check_month < $nowplus10y) {
                $this->month = $check_month;
            } else {
                throw new \InvalidArgumentException('month given is invalid, older than 10 years or more than 10 years in the future');
            }
        }

        $this->renderer->addAttribute('title', 'Dienstplan');
        $this->renderer->addAttribute('persons', $this->persons);

        $dutyroster = new Dutyroster($this->month);

        $this->dienstplan = $dutyroster->create_or_show_for_month();
        $this->renderer->addAttribute('dienstplan', $this->dienstplan);

        return $this->renderer->render($response, 'home.php', ['name' => 'World']);

        /**
        $response->getBody()->write(json_encode(['hello' => 'world']));
        return $response->withHeader('Content-Type', 'application/json');
         */
    }
}
