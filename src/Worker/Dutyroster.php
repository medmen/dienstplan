<?php
namespace Dienstplan\Worker;

use Dienstplan\Worker\Wishes;
use Dienstplan\Worker\People;
use Odan\Session\SessionInterface;
use Odan\Session\FlashInterface;

class Dutyroster
{
    private array $messages = [];

    // make config and dienstplan protected to allow overwrite by Tests
    protected array $config = [];
    protected array $dienstplan = [];
        private array $statistics = [];
    private array $reasons = [];
    private array $people_available = [];
    private mixed $current_candidate = null;
    private ?string $month_string = null;
    private ?string $month_name = null;
    private ?int $month_int = null;
    private ?int $year_int = null;
    private SessionInterface $session;
    private \DateTimeImmutable $target_month;
    private Wishes $wishes;

    function __construct(SessionInterface $session, \DateTimeImmutable $target_month)
    {
        $this->session = $session;
        $this->flash = $this->session->getFlash();
        $this->target_month = $target_month;
    }
    
    protected function get_wishes_for_month($session, $target_month): void
    {
        //load wishes
        $wishes = new Wishes($session, $target_month);
        return($wishes->load_wishes());
    }

    protected function get_people_for_month($target_month): void
    {
        $people = new People($this->session);
        return ($people->load($target_month));
    }

    protected function get_limits(): array
    {
        /**
        $limits = new Limits();
        $this->limits = $limits->load();
         */
        return( array(
            'limits' => [
                'total' => 5,
                'we' => 2,
                'fr' => 1,
                'max_iterations' => 500
            ]
        ));
    }

    protected function set_month_data_in_config(\DateTimeImmutable $target_month): void
    {
        // merge all config file for month in on big arrray
        $this->month_string = $target_month->format('Y_m');
        $this->month_name = $target_month->format('F');
        $this->month_int = $target_month->format('m');
        $this->year_int = $target_month->format('Y');
        $this->days_in_target_month = cal_days_in_month(CAL_GREGORIAN, $target_month->format('m'), $target_month->format('Y'));
    }

    function create_or_show_for_month()
    {
        // see if duty roster was saved already
        // if yes: return it
        $name_to_find = __DIR__ . '/../../data/dienstplan_' . $this->month_string . '.php';
        if (file_exists($name_to_find)) {
            return include($name_to_find);
        }

        // if we got here, no duty roster has been saved for target month yet,
        // so create it

        if ($this->generate() === true) {
            $this->save();
            return $this->dienstplan;
        }

        $this->flash->add('error', 'could not find a solution :(');
        return array();
    }


    function set_current_candidate($candidate)
    {
        if (in_array($candidate, $this->config['people'])) {
            $this->current_candidate = $candidate;
            return true;
        }

        return false;
    }

    function get_current_candidate()
    {
        return $this->current_candidate;
    }


    function find_working_plan()
    {
        for ($day = 1; $day <= $this->days_in_target_month; $day++) {
            $candidate = $this->find_candidate($day) ?? false;
            if (is_string($candidate)) {
                $this->dienstplan[$day] = $candidate;
                $this->update_statistics($candidate, $day);
            } else {
                // make sure we clear all gathered data
                $this->dienstplan = [];
                $this->statistics = [];
                $this->reasons = [];
                $this->debug = [];
                return false;
            }
        }
        return true;
    }


    function generate()
    {
        $max_iteration = $this->config['limits']['max_iterations'] ?? 0;
        for ($i = 1; $i < $max_iteration; $i++) {
            if ($this->find_working_plan()) {
                $this->add_message("after $i iterations i found a working solution :-)");
                return true;
            }
        }
        // if we got here, no solution was found
        $this->add_message("after $i iterations i found NO working solution ;-/ <br> consider adapting the limits<br>");
        return false;
    }

    function find_candidate($day)
    {
        $candidate = null;

        shuffle($this->people_available); // randomize order

        /**
         * see if wishes exist for current day,
         * manage fair (random) choice if more than 1 wish exists
        */
        $arr_wishes_fulfilled = $this->candidates_have_duty_wish($day, $people_available);
        shuffle($arr_wishes_fulfilled); //randomize
        foreach ($arr_wishes_fulfilled as $candidate) {
            if ($this->had_duty_previous_day($candidate, $day)) {
                continue;
                // candidate is not ALLOWED 2 consecutive duties
            }
            return $candidate;
        }

        /**
         * if we got here no wishes exist for $day
         * so lets exclude other wishes and see who's left
         */
        foreach ($people_available as $candidate) {
            $this->set_current_candidate($candidate);

            if ($this->has_noduty_wish($candidate, $day)) {
                $this->reasons[] = [$day, $candidate, 'noduty_wish'];
                continue;
            }

            if ($this->had_duty_previous_day($candidate, $day)) {
                $this->reasons[] = [$day, $candidate, 'duty_prev'];
                continue;
            }

            if ($this->is_on_vacation($candidate, $day)) {
                $this->reasons[] = [$day, $candidate, 'urlaub'];
                continue;
            }
            if ($this->limit_reached_total($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=5);
                $this->reasons[] = [$day, $candidate, 'limit_total'];
                continue;
            }

            if ($this->limit_reached_weekend($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=5);
                $this->reasons[] = [$day, $candidate, 'limit_we'];
                continue;
            }

            if ($this->is_uneven_distribution($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=1);
                $this->reasons[] = [$day, $candidate, 'uneven'];
                continue;
            }
            // All checks passed, include candidate

            return $candidate;
        }

        return $candidate;
    }

    function has_noduty_wish(string $candidate, int $day): bool
    {
        if ($this->config['wishes'][$candidate][$day] == 'F') {
            return true;
        }
        return false;
    }

    function candidates_have_duty_wish(int $day): array
    {
        $candidates_arr = [];
        foreach ($this->people_available as $candidate) {
            if (!array_key_exists($candidate, $this->config['wishes'])) {
                continue;
            }

            if (!array_key_exists($day, $this->config['wishes'][$candidate])) {
                continue;
            }

            if($this->config['wishes'][$candidate][$day] == 'D') {
                    $candidates_arr[] = $candidate;
            }
        }
        return $candidates_arr;
    }

    function is_uneven_distribution($candidate, $day)
    {
        // i sassume that statistics should be filled only until today
        $day_of_week = $this->full_date($day)->format('N');

        switch ($day_of_week) {
            case 5:
                $day_type = 'fr';
                break;
            case 6:
            case 7:
                $day_type = 'we';
            default:
                $day_type = 'woche';
        }

        $day_type = $this->statistics[$candidate][$day_type] ?? null;
        $avg_day_type = $this->statistics['average'][$day_type] ?? null;

        if ($day_type > $avg_day_type and $avg_day_type > 0) {
            return true;
        }

        return false;
    }

    function had_duty_previous_day($candidate, $day)
    {
        $dp = $this->dienstplan;
        // TODO: deal with first day of month: look for last day of previous month somehow
        if (!is_array($this->dienstplan)) {
            return false;
        }

        $day = $day - 1; // shift to previous day for comparison

        if ($day < 1 or $day > 31) {
            return false;
        }

        if (!is_array($this->dienstplan)) {
            return false;
        }

        if (!isset($this->dienstplan[$day])) {
            return false;
        }

        if ($candidate == $this->dienstplan[$day]) {
            return true;
        }

        return false;
    }

    function is_on_vacation($candidate, $day)
    {
        $target_day = $this->full_date($day);

        // make sure urlaub is array
        if (!is_array($this->config['urlaub'])) {
            return false;
        }

        // make sure candidate is in urlaub array
        if (!in_array($candidate, $this->config['urlaub'])) {
            return false;
        }

        foreach ($this->config['urlaub'][$candidate] as $urlaub_range) {
            /**
             * urlaub_range by convention can be either a range of dates consisting
             * of a start date and end date or a single date
             **/
            // very crude check, TODO: make date storage and retrieval much safer
            $date_limits = explode('~', $urlaub_range);
            if (count($date_limits) == 2) {
                $start_date = new DateTime(trim($date_limits[0]));
                $end_date = new DateTime(trim($date_limits[1]));
            } else { // assume only a single date entry is given, see @TODO for this
                $start_date = $end_date = new DateTime(trim($date_limits[0]));
            }

            if (isInDateRange($target_day, $start_date, $end_date)) {
                // candidate is on vacation at given date
                return true;
            }
        }

        return false;
    }

    function limit_reached_total($candidate, $day)
    {

        $candidate_total = intval($this->statistics[$candidate]['total']) ?? 0;
        $limit_total = intval($this->config['limits']['total']) ?? 0;

        if ($candidate_total >= $limit_total and $limit_total > 0) {
            return true;
        }

        return false;
    }

    function limit_reached_weekend($candidate, $day)
    {
        if ($this->statistics[$candidate]['we'] >= $this->config['limits']['we']) {
            return true;
        }
        return false;
    }

    function limit_reached_friday($candidate, $day)
    {
        if ($this->statistics[$candidate]['fr'] >= $this->config['limits']['fr']) {
            return true;
        }
        return false;
    }

    function update_statistics($candidate, $day)
    {
        $day_of_week = $this->full_date($day)->format('N');
        // initialize arrays
        if (!is_array($this->statistics['maximum'])) {
            $this->statistics['maximum']['fr'] = 0;
            $this->statistics['maximum']['we'] = 0;
            $this->statistics['maximum']['woche'] = 0;
        }

        if (!is_array($this->statistics['average'])) {
            $this->statistics['average']['fr'] = 0;
            $this->statistics['average']['we'] = 0;
            $this->statistics['average']['woche'] = 0;
        }

        if (!is_array($this->statistics[$candidate])) {
            $this->statistics[$candidate]['fr'] = 0;
            $this->statistics[$candidate]['we'] = 0;
            $this->statistics[$candidate]['woche'] = 0;
            $this->statistics[$candidate]['total'] = 0;
        }

        switch ($day_of_week) {
            case 5:
                // its a friday
                $this->statistics[$candidate]['fr']++;
                // set maximum if we breach it
                if ($this->statistics[$candidate]['fr'] > $this->statistics['maximum']['fr']) {
                    $this->statistics['maximum']['fr'] = $this->statistics[$candidate]['fr'];
                }
                break;
            case 6:
            case 7:
                // its a weekend
                $this->statistics[$candidate]['we']++;
                // set maximum if we breach it
                if ($this->statistics[$candidate]['we'] > $this->statistics['maximum']['we']) {
                    $this->statistics['maximum']['we'] = $this->statistics[$candidate]['we'];
                }
                break;
            default:
                $this->statistics[$candidate]['woche']++;
                // set maximum if we breach it
                if ($this->statistics[$candidate]['woche'] > $this->statistics['maximum']['woche']) {
                    $this->statistics['maximum']['woche'] = $this->statistics[$candidate]['woche'];
                }
        }
        $this->statistics[$candidate]['total']++;

        // calculate averages
        $count_we = 0;
        $count_fr = 0;
        $count_woche = 0;
        $count_cand = 0;
        foreach ($this->statistics as $cand => $stat) {
            if (in_array($cand, ['maximum', 'total', 'average'])) {
                continue;
            }
            $count_cand++;
            $count_we = $count_we + $stat['we'];
            $count_fr = $count_fr + $stat['fr'];
            $count_woche = $count_woche + $stat['woche'];
        }
        if ($count_cand > 0) {
            $this->statistics['average']['we'] = number_format($count_we / $count_cand, 2, ',', '.');
            $this->statistics['average']['fr'] = number_format($count_fr / $count_cand, 2, ',', '.');
            $this->statistics['average']['woche'] = number_format($count_woche / $count_cand, 2, ',', '.');
        }
    }

    function display($content = 'all')
    {
        $tbl = "<table><thead><tr><th>TAG</th><th>Diensthabende(r)</th></tr></thead>";
        $tbl .= "<tfoot><tr><td colspan=2>yet another GaLF gimmik</td></tr></tfoot>";
        foreach ($this->dienstplan as $dday => $cand) {
            $day_of_week = $this->full_date($dday)->format('N');
            switch ($day_of_week) {
                case 5:
                    $class = ' class="fr" ';
                    break;
                case 6:
                case 7:
                    $class = ' class="we" ';
                    break;
                default:
                    $class = "";
            }
            $tbl .= "<tr $class><td>$dday</td><td>$cand</td></tr>";
        }
        $tbl .= "</table><hr>";

        $stat_tbl = "<table><thead><tr><th>Name</th><th>Woche</th><th>Fr</th><th>We</th><th>Total</th></tr></thead>";
        $stat_tbl .= "<tfoot><tr><td colspan=5>yet another GaLF gimmik</td></tr></tfoot>";


        // sort alphabetically; leave out average and maximum
        $average = $this->statistics['average'] ?? 0;
        $maximum = $this->statistics['maximum'] ?? 0;
        unset($this->statistics['average']);
        unset($this->statistics['maximum']);
        ksort($this->statistics); //sort statistics by name of persons, preserve array keys!
        $this->statistics['average'] = $average;
        $this->statistics['maximum'] = $maximum;

        foreach ($this->statistics as $name => $dienste) {
            $d_woche = $dienste['woche'] ?? 0;
            $d_fr = $dienste['fr'] ?? 0;
            $d_we = $dienste['we'] ?? 0;

            if (!in_array($name, ['average', 'maximum'])) {
                $total = $d_woche + $d_fr + $d_we;
            } else {
                $total = '';
            }
            $stat_tbl .= "<tr><td>$name</td><td>" . $d_woche . "</td><td>" . $d_fr . "</td><td>" . $d_we . "</td><td>$total</td></>";
        }
        $stat_tbl .= "</table><hr>";

        $debug_tbl = "<table><thead><tr><th>TAG</th><th>NAME</th><th>GETH/GEHT-NICHT GRUND</th></tr></thead>";
        $debug_tbl .= "<tfoot><tr><td colspan=3>yet another GaLF gimmik</td></tr></tfoot>";
        foreach ($this->reasons as $dbg) {
            $debug_tbl .= "<tr><td>" . $dbg[0] . "</td><td>" . $dbg[1] . "</td><td>" . $dbg[2] . "</td></tr>";
        }
        $debug_tbl .= "</table><hr>";

        switch ($content) {
            case 'dutyroster':
                return $tbl;
                break; // this break should be unnecessary
            case 'statistics':
                return $stat_tbl;
                break;
            case 'debug':
                return $debug_tbl;
                break;
            case 'alldebug':
                return ($tbl . "<hr>" . $stat_tbl . "<hr>" . $debug_tbl);
                break;
            default:
                return ($tbl . "<hr>" . $stat_tbl);
        }
    }

    /**
     * @return string
     */
    function save()
    {
        if (!is_array($this->dienstplan)) {
            throw new \ErrorException('Cannot save - no dienstplan array available');
        }

        $header = [
            'Nr' => 'integer',
            'Tag' => 'string',
            'Diensthabender' => 'string',
        ];

        $sheetheader =  $this->readable_month;
        $filename = 'data/dienstplan_' . $this->month_string;

        $file_content = "<?php\n";
        $file_content .= '$dutyroster = ' . var_export($this->dienstplan, true) . ";\n";
        $file_content .= '$statistics = ' . var_export($this->statistics, true) . ";\n";
        $file_content .= '$reasons = ' . var_export($this->reasons, true) . ";\n";
        file_put_contents($filename . '.php', $file_content);

        return '#' . floor((memory_get_peak_usage()) / 1024) . "KB" . "\n";
    }

    function load()
    {
        // initialize variables to avoid php8 incompatibilities
        $dienstplan = null;
        $statistics = null;
        $reasons = null;

        $filename = 'data/dienstplan_' . $this->target_year . '_' . $this->target_month . '.php';
        if (!file_exists($filename)) {
            return false;
        }
        include($filename);

        if (is_array($dienstplan)) {
            $this->dienstplan = $dienstplan;
        }

        if (is_array($statistics)) {
            $this->statistics = $statistics;
        }

        if (is_array($reasons)) {
            $this->reasons = $reasons;
        }
        return true;
    }
    // HELPER FUNCTIONS
    function getdebug()
    {
        $is_debug_set = $this->config['general']['debug'] ?? false;
        if (true == $is_debug_set) {
            return $this->array_flatten($this->debug);
        } else {
            return false;
        }
    }

    function array_flatten($array = null)
    {
        $result = [];

        if (!is_array($array)) {
            $array = func_get_args();
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result = array_merge($result, [$key => $value]);
            }
        }
        return $result;
    }

    function full_date($target_day)
    {
        $fulldate = $this->year_int . '-' . $this->month_int . '-' . $target_day;
        return(new \DateTime(trim($fulldate)));
    }

    function add_message($message, $level = 'debug')
    {
        $this->messages[$level][] = $message;
    }

    function add_message_once($id, $message)
    {
        if (!isset($this->$id)) {
            $this->messages[] = $message;
            $this->$id = 'sent_before';
        }
    }

    function show_messages()
    {
        return(implode("<br>\n", $this->messages));
    }
}
