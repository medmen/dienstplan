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
    private \DateTime $month;
    private mixed $dienstplan;
    private SessionInterface $session;
    private FlashInterface $flash;

    public function __construct(Config $config, PhpRenderer $renderer, SessionInterface $session)
    {
        // Read settings
        $this->persons = serialize($config->get("people"));
        $this->renderer = $renderer;
        $this->month = new \DateTime('now');
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
            $check_month = \DateTime::createFromFormat('m/Y', $month_given);

            $nowplus10y = $this->month->add(new DateInterval('P10Y'));
            $nowminus10y = $this->month->sub(new DateInterval('P10Y'));

            // instanceof makes sure PHPStan doesnt complain
            if($nowminus10y < $check_month and $check_month < $nowplus10y and $check_month instanceof \DateTime) {
                $this->month = $check_month;
                $this->flash->add('info', 'selected month is within 10 years from now');
            } else {
                throw new \InvalidArgumentException('month given is invalid, older than 10 years or more than 10 years in the future');
            }
        }
        $this->flash->add('info', 'selected month is within 10 years from now');

        $formatted_monthyear = \IntlDateFormatter::formatObject($this->month, "MMMM y");

        $this->renderer->addAttribute('flash', $this->flash->all());
        $this->renderer->addAttribute('title', 'Dienstplan für '.$formatted_monthyear);
        $this->renderer->addAttribute('persons', $this->persons);
        $this->renderer->addAttribute('user', $this->session->get('user'));

        $dutyroster = new Dutyroster($this->month);

        $this->dienstplan = $dutyroster->create_or_show_for_month();
        $this->renderer->addAttribute('dienstplan', $this->dienstplan);

        return $this->renderer->render($response, 'home.php', ['name' => 'World']);
    }
}
