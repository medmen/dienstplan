<?php

namespace Dienstplan\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use Dienstplan\Support\Config;
use Dienstplan\Worker\Wishes;
use DateTimeImmutable;
use DateInterval;
use Odan\Session\SessionInterface;

final class WishAction
{
    private PhpRenderer $renderer;

    private $persons, $month, $dienstplan, $session, $flash;

    public function __construct(Config $config, PhpRenderer $renderer, SessionInterface $session)
    {
        // Read settings
        $this->persons = serialize($config->get("people"));
        $this->renderer = $renderer;
        $this->month = new DateTimeImmutable('now');
        $this->session = $session;
    }


    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $flash = $this->session->getFlash();
        $flash->add('info', 'Invoking Wish Action');

        // if no month was given, use actual month
        // $haeh = $args['target_month'];
        $month_given = $request->getQueryParams()['target_month'];

        if (is_null($month_given) === false) {
            // sanity check: make sure date given is between -10 and + 10 years from now
            $check_month = DateTimeImmutable::createFromFormat('m/Y', $month_given);
            $next_month = $check_month->add(new DateInterval('P1M'));

            if ($nowminus10y < $next_month and $next_month < $nowplus10y) {
                $this->month = $next_month;
                $flash->add('info', 'selected month is within 10 years from now');
            } else {
                throw new \InvalidArgumentException('month given is invalid, older than 10 years or more than 10 years in the future');
            }
        }
        $flash->add('info', 'selected month is within 10 years from now');

        $formatted_monthyear = \IntlDateFormatter::formatObject($this->month, "MMMM y");
        $last_day_in_month = $this->month->modify("last day of this month");

        $this->renderer->addAttribute('flash', $flash->all());
        $this->renderer->addAttribute('title', 'Wunsch für ' . $formatted_monthyear);
        $this->renderer->addAttribute('persons', $this->persons);
        $this->renderer->addAttribute('days_in_month', $last_day_in_month->format("d"));
        $wishes = new Wishes($this->month);

        $this->wuensche = $wishes->load_wishes();
        $this->renderer->addAttribute('wuensche', $this->wuensche);

        return $this->renderer->render($response, 't_wishes.php', ['name' => 'World']);

        /**
         * $response->getBody()->write(json_encode(['hello' => 'world']));
         * return $response->withHeader('Content-Type', 'application/json');
         */
    }
}
