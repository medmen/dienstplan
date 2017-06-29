<?php
/**
 * Created by PhpStorm.
 * User: galak
 * Date: 23.03.17
 * Time: 23:00
 */

ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);


class dienstplan {
    function __construct()
    {
        // load people
        require_once('./config/people.php');
        require_once('./config/limits.php');
        include_once('./config/urlaub.php');

        // determine next month
        $now = getdate();
        $this->target_month = sprintf('%02d', $now['mon'] + 1);
        $this->target_year = $now['mon'] == 12 ? $now['year'] + 1 : $now['year'];

        include_once('./config/wishes_'.$this->target_month.'_'.$this->target_year.'.php');

        $this->days_in_target_month = cal_days_in_month(CAL_GREGORIAN, $this->target_month, $this->target_year);

        $this->dienstplan = array();
        $this->statistics = array();
        $this->debug = array();
    }

    function find_working_plan() {
        for($day = 1; $day <= $this->days_in_target_month; $day++) {
            $candidate = false;
            $candidate = $this->find_candidate($day);
            if($candidate) {
                $this->dienstplan[$day] = $candidate;
                $this->update_statistics($candidate, $day);
            } else {
                // make sure we clear all gathered data
                $this->dienstplan = array();
                $this->statistics = array();
                $this->debug = array();
                return false;
            }
        }
        return true;
    }


    function generate() {
        global $config;
        for ($i=1; $i < $config['limits']['max_iterations']; $i++) {
            if(true == $this->find_working_plan()) {
                $this->message.= "after $i iterations i found a working solution :-)";
               return;
            }
        }
        // if we got here, no solution was found
        $this->message.= "after $i iterations i found NO working solution ;-/ <br> consider adapting the limits<br>";
    }

    function find_candidate($day) {
        global $config, $softlimits;
        // start with random people
        $people_available = $config['people'];
        shuffle($people_available); // randomize order
        foreach($people_available as $candidate) {
            if($this->had_duty_previous_day($candidate, $day)) {
               $this->debug[] = array($day, $candidate, 'duty_prev');
                continue;
            }

            if($this->is_on_vacation($candidate, $day)) {
                $this->debug[] = array($day, $candidate, 'urlaub');
                continue;
            }
            if($this->limit_reached_total($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=5);
                $this->debug[] = array($day, $candidate, 'limit_total');
                continue;
            }

            if($this->limit_reached_weekend($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=5);
                $this->debug[] = array($day, $candidate, 'limit_we');
                continue;
            }

            if($this->is_uneven_distribution($candidate, $day)) {
                // $softlimits[$day][] = array($candidate, $priority=1);
                $this->debug[] = array($day, $candidate, 'uneven');
                continue;
            }
            // All checks passed, include candidate
            return $candidate;
        }
    }

    function is_uneven_distribution($candidate, $day) {
        // i sassume that statistics should be filled only until today
        $day_of_week = date('w', $this->full_date($day));
        $day_type = null;
        switch ($day_of_week) {
            case 4:
                $day_type = 'fr';
                break;
            case 5:
            case 6:
                $day_type = 'we';
            default:
                $day_type = 'woche';
        }

        /**
         * if($this->statistics[$candidate][$day_type] >= $this->statistics['maximum'][$day_type]
         * and $this->statistics['maximum'][$day_type] > 0
         ) {
            return true;
        }
         **/

        if($this->statistics[$candidate][$day_type] > $this->statistics['average'][$day_type] and $this->statistics['average'][$day_type] > 0) {
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
        if($this->statistics[$candidate]['total'] >= $config['limits']['total']) {
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
        $day_of_week = date('w', $this->full_date($day));
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
            case 4:
                // its a friday
                $this->statistics[$candidate]['fr']++;
                // set maximum if we breach it
                if($this->statistics[$candidate]['fr'] > $this->statistics['maximum']['fr']) {
                    $this->statistics['maximum']['fr'] = $this->statistics[$candidate]['fr'];
                }
                break;
            case 5:
            case 6:
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
            // number_format($count_we / $count_cand, 2, ',', '.');
            $this->statistics['average']['we'] = $count_we / $count_cand;
            $this->statistics['average']['fr'] = $count_fr / $count_cand;
            $this->statistics['average']['woche'] = $count_woche / $count_cand;
        }
    }

    function display($content='all') {
        $tbl = "<table><thead><tr><th>TAG</th><th>Diensthabende(r)</th></tr></thead>";
        $tbl.= "<tfoot><tr><td colspan=2>yet another GaLF gimmik</td></tr></tfoot>";
        foreach ($this->dienstplan as $dday => $cand) {
            $day_of_week = date('w', $this->full_date($dday));
            switch($day_of_week) {
                case 4:
                    $class = ' class="fr" ';
                    break;
                case 5:
                case 6:
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
        ksort($this->statistics); //sort statistics by name of persons, preserve array keys!
        foreach ($this->statistics as $name => $dienste) {
            $total = $dienste['woche'] + $dienste['fr'] + $dienste['we'];
            $stat_tbl.= "<tr><td>$name</td><td>".$dienste['woche']."</td><td>".$dienste['fr']."</td><td>".$dienste['we']."</td><td>$total</td></>";
        }
        $stat_tbl.= "</table><hr>";

        $debug_tbl = "<table><thead><tr><th>TAG</th><th>NAME</th><th>GEHT-NICHT-GRUND</th></tr></thead>";
        $debug_tbl.= "<tfoot><tr><td colspan=3>yet another GaLF gimmik</td></tr></tfoot>";
        foreach ($this->debug as $dbg) {
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

    // HELPER FUNCTIONS
    function isInDateRange(DateTime $date, DateTime $startDate, DateTime $endDate) {
        return $date >= $startDate && $date <= $endDate;
    }

    function full_date($target_day) {
        return(strtotime($this->target_year.'-'.$this->target_month.'-'.$target_day));
    }

}
?>