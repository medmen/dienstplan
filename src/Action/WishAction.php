<?php

namespace Dienstplan\Action;

use Dienstplan\Worker\People;
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

    public function __construct(
        Config $config,
        PhpRenderer $renderer,
        SessionInterface $session
    )
    {
        // Read settings
        $this->persons = serialize($config->get("people"));
        $this->renderer = $renderer;
        $this->month = new DateTimeImmutable('now');
        $this->session = $session;
    }


    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->flash = $this->session->getFlash();
        $this->flash->clear(); // clear flash messages
        $this->flash->add('info', 'Invoking Wish Action');

        // if no month was given, use actual month
        // $haeh = $args['target_month'];
        $month_given = $request->getQueryParams()['target_month'];

        // isDateWithinLast10Years is defined in /Support/functions.php
        if (!is_null($month_given)) {
            if (isDateWithinLast10Years($month_given)) {
                $this->month = \DateTimeImmutable::createFromFormat('m/Y', $month_given);
            } else {
                $this->flash->add('error', 'selected month is older or younger than 10 years, using actual month instead');
            }
        }

        $formatted_monthyear = \IntlDateFormatter::formatObject($this->month, "MMMM y");

        $calendarmonth = generateWeekdaysForMonth($this->month);
        $last_day_in_month = $this->month->modify("last day of this month")->setTime(0,0,1); // make sure end date is a bit later than start to avoid rounding issues in period


        $people = new People($this->session);
        $wishes = new Wishes($this->session, $people);

        $this->wuensche = $wishes->get_wishes_for_month($this->month, true); // second parameter fetches people without wishes for mpnth

        $this->renderer->addAttribute('wishes', $this->wuensche);
        $this->renderer->addAttribute('flash', $this->flash->all());
        $this->renderer->addAttribute('title', 'Wunsch für ' . $formatted_monthyear);
        $this->renderer->addAttribute('persons', $this->persons);
        $this->renderer->addAttribute('calendarmonth', $calendarmonth);
        $this->renderer->addAttribute('days_in_month', $last_day_in_month->format("d"));
        $this->renderer->addAttribute('user', $this->session->get('user'));

        return $this->renderer->render($response, 't_wishes.php', ['target_month' => $this->month->format('U')]);
    }
}
