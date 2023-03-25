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
use Odan\Session\SessionManagerInterface;

final class WishAction
{
    private PhpRenderer $renderer;
    private SessionManagerInterface $sessionManager;
    private $persons, $month, $dienstplan, $session, $flash;

    public function __construct(Config $config, PhpRenderer $renderer, SessionInterface $session, SessionManagerInterface $sessionManager)
    {
        // Read settings
        $this->persons = serialize($config->get("people"));
        $this->renderer = $renderer;
        $this->month = new DateTimeImmutable('now');
        $this->session = $session;
        $this->sessionManager = $sessionManager;
    }


    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->sessionManager->destroy();
        $this->sessionManager->start();
        $this->sessionManager->regenerateId();
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
                // $flash->add('info', 'selected month is within 10 years from now');
            } else {
                throw new \InvalidArgumentException('month given is invalid, older than 10 years or more than 10 years in the future');
            }
        }

        $formatted_monthyear = \IntlDateFormatter::formatObject($this->month, "MMMM y");
        $first_day_in_month = $this->month->modify("first day of this month")->setTime(0,0,0); // make sure to zero time of this day to avoid rounding issues in period used later
        $last_day_in_month = $this->month->modify("last day of this month")->setTime(0,0,1); // make sure end date is a bit later than start to avoid rounding issues in period
        $interval = DateInterval::createFromDateString('1 day');
        $daterange = new \DatePeriod($first_day_in_month, $interval ,$last_day_in_month);

        $calendarmonth = array();
        foreach($daterange as $day) {
            $calendarmonth[$day->format('d')] = $day->format('N'); // 'N' = 1 Monday, 7 Sunday
            //@TODO implement holidays!
        }

        $this->renderer->addAttribute('flash', $flash->all());
        $this->renderer->addAttribute('title', 'Wunsch für ' . $formatted_monthyear);
        $this->renderer->addAttribute('persons', $this->persons);
        $this->renderer->addAttribute('days_in_month', $last_day_in_month->format("d"));
        $this->renderer->addAttribute('calendarmonth', $calendarmonth);
        $wishes = new Wishes($this->month);

        $this->wuensche = $wishes->load_wishes();
        $this->renderer->addAttribute('wishes', $this->wuensche);

        return $this->renderer->render($response, 't_wishes.php', ['target_month' => $this->month->format('U')]);
    }
}
