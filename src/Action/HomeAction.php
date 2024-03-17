<?php
namespace Dienstplan\Action;

use Dienstplan\Worker\People;
use Dienstplan\Worker\Wishes;
use Odan\Session\FlashInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use Dienstplan\Support\Config;
use Dienstplan\Worker\Dutyroster;
use DateTimeImmutable;
use DateInterval;
use Odan\Session\SessionInterface;

final class HomeAction
{
    private PhpRenderer $renderer;
    private \DateTimeImmutable $month;
    private mixed $dienstplan;
    private SessionInterface $session;
    private FlashInterface $flash;

    public function __construct(Config $config, PhpRenderer $renderer, SessionInterface $session)
    {
        // Read settings
        // $this->persons = serialize($config->get("people"));
        $this->renderer = $renderer;
        $this->month = new \DateTimeImmutable('now');
        $this->session = $session;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        // make sure we clean flash messages and reinitialize
        $this->flash = $this->session->getFlash();
        $this->flash->clear(); // clear flash messages
        $this->flash->add('info', 'Invoking Home Action');

        // if no month was given, use actual month
        $month_given = $request->getQueryParams()['target_month'];
        if (!is_null($month_given)) {
            if (isDateWithinLast10Years($month_given)) {         // isDateWithinLast10Years is defined in /Support/functions.php
                $this->month = \DateTimeImmutable::createFromFormat('m/Y', $month_given);
            } else {
                $this->flash->add('error', 'selected month is older or younger than 10 years, using actual month instead');
            }
        }
        $formatted_monthyear = \IntlDateFormatter::formatObject($this->month, "MMMM y");

        // collect Data for month to generate Dutyroster
        $people = new People($this->session);
        $wishes = new Wishes($this->session, $people);
        //$rules = new Rules($this->session);
        //$limits = new Limits($this->session);

        // the core process: get dutyroster
        $dutyroster = new Dutyroster($this->session, $people, $wishes);
        $this->dienstplan = $dutyroster->create_or_show_for_month($this->month);
        $calendarmonth = generateWeekdaysForMonth($this->month);         // generteWeekdaysForMonth is defined in /Support/functions.php

        $this->renderer->addAttribute('dienstplan', $this->dienstplan);
        $this->renderer->addAttribute('flash', $this->flash->all());
        $this->renderer->addAttribute('title', 'Dienstplan für '.$formatted_monthyear);
        $this->renderer->addAttribute('calendarmonth', $calendarmonth);
        // $this->renderer->addAttribute('persons', $this->persons);
        $this->renderer->addAttribute('user', $this->session->get('user'));

        return $this->renderer->render($response, 'home.php', ['name' => 'World']);
    }
}
