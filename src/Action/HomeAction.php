<?php
namespace Dienstplan\Action;

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
    private mixed $persons;
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
        $this->flash = $this->session->getFlash();
        $this->flash->add('info', 'Invoking Home Action');

        // if no month was given, use actual month
        $month_given = $request->getQueryParams()['target_month'];

        if(!is_null($month_given)) {
            // sanity check: make sure date given is between -10 and + 10 years from now
            $check_month = \DateTimeImmutable::createFromFormat('m/Y', $month_given);

            //make sure to override current mont (as set in __construct)
            $this->month = $check_month;
            $tenYearInterval = new \DateInterval('P10Y');
            $nowplus10y = $check_month->add($tenYearInterval);
            $nowminus10y = $check_month->sub($tenYearInterval);

            // instanceof makes sure PHPStan doesnt complain
            if($nowminus10y < $check_month and $check_month < $nowplus10y and $check_month instanceof \DateTimeImmutable) {
                $this->flash->add('info', 'selected month is within 10 years from now');
            } else {
                $this->flash->add('error', 'month given is invalid, older than 10 years or more than 10 years in the future');
            }
        }

        $formatted_monthyear = \IntlDateFormatter::formatObject($this->month, "MMMM y");

        $this->renderer->addAttribute('flash', $this->flash->all());
        $this->renderer->addAttribute('title', 'Dienstplan für '.$formatted_monthyear);
        // $this->renderer->addAttribute('persons', $this->persons);
        $this->renderer->addAttribute('user', $this->session->get('user'));

        $dutyroster = new Dutyroster($this->session, $this->month);

        $this->dienstplan = $dutyroster->create_or_show_for_month();
        $this->renderer->addAttribute('dienstplan', $this->dienstplan);

        return $this->renderer->render($response, 'home.php', ['name' => 'World']);
    }
}
