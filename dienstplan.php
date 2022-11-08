<?php
namespace Dienstplan;
/**
 * Created by PhpStorm.
 * User: galak
 * Date: 23.03.17
 * Time: 23:00
 */

ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);


class dienstplan {
    private $messages = array();

    function __construct()
    {
        $this->config = require_once('./config/general.php');
        // load people
        $this->config = array_merge($this->config, require_once('./config/people.php'));
        $this->config = array_merge($this->config, require_once('./config/limits.php'));
        $this->config = array_merge($this->config, require_once('./config/urlaub.php'));

        // determine next month
        $now = getdate();
        $this->target_month = sprintf('%02d', $now['mon'] + 1);
        $this->target_year = $now['mon'] == 12 ? $now['year'] + 1 : $now['year'];
        $monat = new \DateTime($this->target_year.'-'.$this->target_month);
        setlocale(LC_TIME, 'de_DE.UTF-8');
        $this->readable_month = strftime("%B %Y", $monat->getTimestamp());

        $wishes_file = './config/wishes_'.$this->target_month.'_'.$this->target_year.'.php';
        if (file_exists($wishes_file)) {
            include_once($wishes_file);
        }

        $this->days_in_target_month = cal_days_in_month(CAL_GREGORIAN, $this->target_month, $this->target_year);

        $this->dienstplan = array();
        $this->statistics = array();
        $this->reasons = array();
        $this->debug = array();
        $this->current_candidate = null;
    }

    function set_current_candidate($candidate) {
        global $config;

        if(in_array($candidate, $this->config['people'])) {
            $this->current_candidate = $candidate;
            return true;
        }

        return false;
    }

    function get_current_candidate() {
        return $this->current_candidate;
    }


    function find_working_plan() {
        for($day = 1; $day <= $this->days_in_target_month; $day++) {
            $candidate = $this->find_candidate($day) ?? false;
            if(is_string($candidate)) {
                $this->dienstplan[$day] = $candidate;
                $this->update_statistics($candidate, $day);
            } else {
                // make sure we clear all gathered data
                $this->dienstplan = array();
                $this->statistics = array();
                $this->reasons = array();
                $this->debug = array();
                return false;
            }
        }
        return true;
    }


    function generate() {
        global $config;
        $max_iteration = $config['limits']['max_iterations'] ?? 0;
        for ($i=1; $i < $max_iteration; $i++) {
            if($this->find_working_plan()) {
                $this->add_message("after $i iterations i found a working solution :-)");
               return;
            }
        }
        // if we got here, no solution was found
        $this->add_message("after $i iterations i found NO working solution ;-/ <br> consider adapting the limits<br>");
    }

    function find_candidate($day) {
        global $config, $softlimits;
        // people may have additional data attached to their username, lets check
        $people_available = array();
        $candidate = null;

        foreach($config['people'] as $i => $ppl) {
            if(is_array($ppl)) {
                $people_available[] = $i;
            }

            if(is_string($ppl)) {
                $people_available[] = $ppl;
            }
        }

        shuffle($people_available); // randomize order

        // see if wishes exist for current day
        $wish_fulfilled = $this->candidate_has_duty_wish($day, $people_available);
        if($wish_fulfilled and !$this->had_duty_previous_day($wish_fulfilled, $day)) {
            // TODO: check if we need more tests here to avoid unfair treatment
            $this->reasons[] = array($day, $wish_fulfilled, 'wish_granted');
            return $wish_fulfilled;
        }

        foreach($people_available as $candidate) {
            $this->set_current_candidate($candidate);

            if($this->has_noduty_wish($candidate, $day)) {
                $this->reasons[] = array($day, $candidate, 'noduty_wish');
                continue;
            }

            if($this->had_duty_previous_day($candidate, $day)) {
               $this->reasons[] = array($day, $candidate, 'duty_prev');
                continue;
            }

            if($this->is_on_vacation($candidate, $day)) {
                $this->reasons[] = array($day, $candidate, 'urlaub');
                continue;
            }
            if($this->limit_reached_total($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=5);
                $this->reasons[] = array($day, $candidate, 'limit_total');
                continue;
            }

            if($this->limit_reached_weekend($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=5);
                $this->reasons[] = array($day, $candidate, 'limit_we');
                continue;
            }

            if($this->is_uneven_distribution($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=1);
                $this->reasons[] = array($day, $candidate, 'uneven');
                continue;
            }
            // All checks passed, include candidate

            return $candidate;
        }

        return $candidate;
    }

    function has_noduty_wish($candidate, $day) {
        global $config;
        $wishes_file = './config/wishes_'.$this->target_month.'_'.$this->target_year.'.php';
        if (file_exists($wishes_file)) {
            include_once($wishes_file);
        } else {
            // do not repeat this message over and over again
            $this->add_message_once('noduty_file_missing','für den Monat '.$this->target_month.'/'.$this->target_year.' existieren noch keine Wünsche in '.__FUNCTION__.'!');
            return false;
        }

        // for date comparison we need to turn $day into a date object
        $day = $this->full_date($day);
        if(!is_array($config['wishes']['noduty'][$candidate])) {
            //no wishes found for $candidate
            return false;
        }

        foreach ($config['wishes']['noduty'][$candidate] as $id => $wish) {
            $wish_limits = explode('~', $wish);

            if(count($wish_limits) == 2) {
                $start_date = new DateTime(trim($wish_limits[0]));
                $end_date = new DateTime(trim($wish_limits[1]));
            } else { // assume only a single date entry is given, see @TODO for this
                $start_date = $end_date = new DateTime(trim($wish_limits[0]));
            }

            if($this->isInDateRange($day, $start_date, $end_date)) {
                $this->debug[] = "for $candidate a noduty wish for ".$start_date->format('Y-m-d')." till ".$end_date->format('Y-m-d')." indludes actual day ".$day->format('Y-m-d');
                return true;
            }
        }
        // if we made it here all test have yielded no result
        return false;
    }

    function candidate_has_duty_wish($day, $people_available) {
        global $config;
        $wishes_file = './config/wishes_'.$this->target_month.'_'.$this->target_year.'.php';
        if (file_exists($wishes_file)) {
            include_once($wishes_file);
        } else {
            $this->add_message_once('duty_file_missing','für den Monat '.$this->target_month.'/'.$this->target_year.' existieren noch keine Wünsche in '.__FUNCTION__.'!');
            return false;
        }

        // for date comparison we need to turn $day into a date object
        $day = $this->full_date($day);

        if(!is_array($config['wishes']['duty'])) {
           return false;
        }

        //randomize wishes preserving keys, otherwise alphabetic sorting of names would prefer certain people
        // see http://php.net/manual/en/function.shuffle.php#121088
        uksort($config['wishes']['duty'], function ($a, $b) {return mt_rand(-10, 10);});

        foreach ($config['wishes']['duty'] as $candidate => $wish_arr) {
            foreach($wish_arr as $wish) {
                /**
                 * wish by convention can be either a range of dates consisting
                 * of a start date and end date or a single date
                 **/
                // very crude check, TODO: make date storage and retrieval much safer
                $wish_limits = explode('~', $wish);
                if(count($wish_limits) == 2) {
                    $start_date = new DateTime(trim($wish_limits[0]));
                    $end_date = new DateTime(trim($wish_limits[1]));
                } else { // assume only a single date entry is given, see @TODO for this
                    $start_date = $end_date = new DateTime(trim($wish_limits[0]));
                }

                if($this->isInDateRange($day, $start_date, $end_date)) {
                    $this->debug[] = "for $candidate a duty wish for ".$day->format('Y-m-d')." comes true :)";
                    return $candidate;
                } else {
                   $this->debug[] = "for $candidate a duty wish between ".$start_date->format('Y-m-d')." and ".$end_date->format('Y-m-d').' does not cover actual day '.$day->format('Y-m-d');
                }
            }
        }
    }

    function is_uneven_distribution($candidate, $day) {
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

        if($day_type > $avg_day_type and $avg_day_type > 0) {
            return true;
        }

        return false;
    }

    function had_duty_previous_day($candidate, $day) {
        // TODO: deal with first day of month: look for last day of previous month somehow
        if(is_array($this->dienstplan) and
            $day > 1 and
            $candidate == $this->dienstplan[$day-1]) { // and array_key_exists($day-1, $dienstplan)
            return true;
        }
        return false;
    }

    function is_on_vacation($candidate, $day) {
        global $config;
        $target_day = $this->full_date($day);

        // make sure candidate is in urlaub array
        if(!in_array($candidate, $config['urlaub'])) {
            return false;
        }

        foreach ($config['urlaub'][$candidate] as $urlaub_range) {
            /**
             * urlaub_range by convention can be either a range of dates consisting
             * of a start date and end date or a single date
             **/
            // very crude check, TODO: make date storage and retrieval much safer
            $date_limits = explode('~', $urlaub_range);
            if(count($date_limits) == 2) {
                $start_date = new DateTime(trim($date_limits[0]));
                $end_date = new DateTime(trim($date_limits[1]));
            } else { // assume only a single date entry is given, see @TODO for this
                $start_date = $end_date = new DateTime(trim($date_limits[0]));
            }

            if(isInDateRange($target_day, $start_date, $end_date)) {
                // candidate is on vacation at given date
                return true;
            }
        }

        return false;
    }

    function limit_reached_total($candidate, $day) {
        global $config;

        $candidate_total = intval($this->statistics[$candidate]['total']) ?? 0;
        $limit_total = intval($config['limits']['total']) ?? 0;

        if($candidate_total >= $limit_total and $limit_total > 0) {
            return true;
        }

        return false;
    }

    function limit_reached_weekend($candidate, $day) {
        global $config;
        if($this->statistics[$candidate]['we'] >= $config['limits']['we']) {
            return true;
        }
        return false;
    }

    function limit_reached_friday($candidate, $day) {
        global $config;
        if($this->statistics[$candidate]['fr'] >= $config['limits']['fr']) {
            return true;
        }
        return false;
    }

    function update_statistics($candidate, $day) {
        $day_of_week = $this->full_date($day)->format('N');
        // initialize arrays
        if(!is_array($this->statistics['maximum'])){
            $this->statistics['maximum']['fr'] = 0;
            $this->statistics['maximum']['we'] = 0;
            $this->statistics['maximum']['woche'] = 0;
        }

        if(!is_array($this->statistics['average'])){
            $this->statistics['average']['fr'] = 0;
            $this->statistics['average']['we'] = 0;
            $this->statistics['average']['woche'] = 0;
        }

        if(!is_array($this->statistics[$candidate])){
            $this->statistics[$candidate]['fr'] = 0;
            $this->statistics[$candidate]['we'] = 0;
            $this->statistics[$candidate]['woche'] = 0;
            $this->statistics[$candidate]['total'] = 0;
        }

        switch($day_of_week) {
            case 5:
                // its a friday
                $this->statistics[$candidate]['fr']++;
                // set maximum if we breach it
                if($this->statistics[$candidate]['fr'] > $this->statistics['maximum']['fr']) {
                    $this->statistics['maximum']['fr'] = $this->statistics[$candidate]['fr'];
                }
                break;
            case 6:
            case 7:
                // its a weekend
                $this->statistics[$candidate]['we']++;
                // set maximum if we breach it
                if($this->statistics[$candidate]['we'] > $this->statistics['maximum']['we']) {
                    $this->statistics['maximum']['we'] = $this->statistics[$candidate]['we'];
                }
                break;
            default:
                $this->statistics[$candidate]['woche']++;
                // set maximum if we breach it
                if($this->statistics[$candidate]['woche'] > $this->statistics['maximum']['woche']) {
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
            if(in_array($cand, array('maximum', 'total', 'average'))) {
                continue;
            }
            $count_cand++;
            $count_we = $count_we + $stat['we'];
            $count_fr = $count_fr + $stat['fr'];
            $count_woche = $count_woche + $stat['woche'];
        }
        if($count_cand > 0) {
            $this->statistics['average']['we'] = number_format($count_we / $count_cand, 2, ',', '.');
            $this->statistics['average']['fr'] = number_format($count_fr / $count_cand, 2, ',', '.');
            $this->statistics['average']['woche'] = number_format($count_woche / $count_cand, 2, ',', '.');
        }
    }

    function display($content='all') {
        $tbl = "<table><thead><tr><th>TAG</th><th>Diensthabende(r)</th></tr></thead>";
        $tbl.= "<tfoot><tr><td colspan=2>yet another GaLF gimmik</td></tr></tfoot>";
        foreach ($this->dienstplan as $dday => $cand) {
            $day_of_week = $this->full_date($dday)->format('N');
            switch($day_of_week) {
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
            $tbl.= "<tr $class><td>$dday</td><td>$cand</td></tr>";
        }
        $tbl.= "</table><hr>";

        $stat_tbl = "<table><thead><tr><th>Name</th><th>Woche</th><th>Fr</th><th>We</th><th>Total</th></tr></thead>";
        $stat_tbl.= "<tfoot><tr><td colspan=5>yet another GaLF gimmik</td></tr></tfoot>";


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

            if(!in_array($name, array('average', 'maximum'))) {
                $total = $d_woche + $d_fr + $d_we;
            } else  {
                $total = '';
            }
            $stat_tbl.= "<tr><td>$name</td><td>".$d_woche."</td><td>".$d_fr."</td><td>".$d_we."</td><td>$total</td></>";
        }
        $stat_tbl.= "</table><hr>";

        $debug_tbl = "<table><thead><tr><th>TAG</th><th>NAME</th><th>GETH/GEHT-NICHT GRUND</th></tr></thead>";
        $debug_tbl.= "<tfoot><tr><td colspan=3>yet another GaLF gimmik</td></tr></tfoot>";
        foreach ($this->reasons as $dbg) {
            $debug_tbl.= "<tr><td>".$dbg[0]."</td><td>".$dbg[1]."</td><td>".$dbg[2]."</td></tr>";
        }
        $debug_tbl.= "</table><hr>";

        switch($content){
            case 'dienstplan':
                return $tbl;
                break; // this break should be unnecessary
            case 'statistics':
                return $stat_tbl;
                break;
            case 'debug':
                return $debug_tbl;
                break;
            case 'alldebug':
                return ($tbl."<hr>".$stat_tbl."<hr>".$debug_tbl);
                break;
            default:
                return ($tbl."<hr>".$stat_tbl);
        }
    }

    /**
     * @return string
     */
    function save() {
        if(!is_array($this->dienstplan)) {
            return false;
        }

        $header = array(
            'Nr' => 'integer',
            'Tag' => 'string',
            'Diensthabender'=>'string',
        );

        $sheetheader =  $this->readable_month;
        $filename = 'data/dienstplan_'.$this->target_year.'_'.$this->target_month;

        $file_content = "<?php\n";
        $file_content.= '$dienstplan = '.var_export($this->dienstplan, true).";\n";
        $file_content.= '$statistics = '.var_export($this->statistics, true).";\n";
        $file_content.= '$reasons = '.var_export($this->reasons, true).";\n";
        file_put_contents($filename.'.php', $file_content);

        /**
        include_once("./vendor/PHP_XLSXWriter/xlsxwriter.class.php");
        $writer = new XLSXWriter();
        $writer->writeSheetHeader($sheetheader, $header );
        foreach($this->dienstplan as $dday => $cand) {
            $day_of_week = $this->full_date($dday)->format('N');
            $row = array($dday,$day_of_week, $cand);
            $writer->writeSheetRow($sheetheader, $row );
        }
        $writer->writeToFile($filename.'.xlsx');

         **/
        return '#'.floor((memory_get_peak_usage())/1024)."KB"."\n";
    }

    function load() {
        // initialize variables to avoid php8 incompatibilities
        $dienstplan = null;
        $statistics = null;
        $reasons = null;

        $filename = 'data/dienstplan_'.$this->target_year.'_'.$this->target_month.'.php';
        if(!file_exists($filename)) {
            return false;
        }
        include ($filename);

        if(is_array($dienstplan)) {
            $this->dienstplan = $dienstplan;
        }

        if(is_array($statistics)) {
            $this->statistics = $statistics;
        }

        if(is_array($reasons)) {
            $this->reasons = $reasons;
        }
        return true;
    }
    // HELPER FUNCTIONS
    function getdebug() {
        global $config;
        $is_debug_set = $config['general']['debug'] ?? false;
        if(true == $is_debug_set) {
            return $this->array_flatten($this->debug);
        } else {
            return false;
        }
    }

    function array_flatten($array = null) {
        $result = array();

        if (!is_array($array)) {
            $array = func_get_args();
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result = array_merge($result, array($key => $value));
            }
        }
        return $result;
    }

    function isInDateRange(DateTime $date, DateTime $startDate, DateTime $endDate) {
        return($date >= $startDate and $date <= $endDate); // true or false
    }

    function full_date($target_day) {
        $fulldate = $this->target_year.'-'.$this->target_month.'-'.$target_day;
        return(new DateTime(trim($fulldate)));
    }

    public function add_message($message) {
        $this->messages[] = $message;
    }

    public function add_message_once($id, $message) {
        if(!isset($this->$id)) {
            $this->messages[] = $message;
            $this->$id = 'sent_before';
        }
    }

    public function show_messages() {
        return(implode("<br>\n", $this->messages));
    }
}
