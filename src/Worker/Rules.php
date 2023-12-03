<?php
declare(strict_types=1);

namespace Dienstplan\Worker;

class Rules
{
    /**
     * config can hold complex arrays, but key is always a name
     * @var array<string, array<string,mixed>>
     */
    private array $config;
    /**
     * conffiles holds array(name => path_to_file)
     * @var array<string, string>
     */
    private array $conffiles;
    private string $month_string;
    private string $month_int;
    private string $year_int;
    private string $path_to_configfiles;
    private array $messages = array();
    function __construct(\DateTimeImmutable $target_month)
    {
        // merge all config file for month in on big arrray
        $this->config = []; // start with pristine array
        $this->month_string = $target_month->format('Y_m');
        $this->month_int = $target_month->format('m');
        $this->year_int = $target_month->format('Y');
        $this->path_to_configfiles = __DIR__.'/../../data/';

        $conffiles['people'] = $this->path_to_configfiles.'people.php';

        foreach(['wishes', 'urlaub'] as $subconf) {
            $conffiles[$subconf] = $this->path_to_configfiles.$subconf.'_'.$this->month_string.'.php';
        }

        $this->conffiles = $conffiles;

        // load people
        if (file_exists($conffiles['people'])) {
            $this->config = array_replace($this->config, require($conffiles['people']));
        }
    }

    function has_noduty_wish(string $candidate, int $day):bool {
        $wishes_file = './config/wishes_'.$this->month_int.'_'.$this->year_int.'.php';
        if (file_exists($wishes_file)) {
            include_once($wishes_file);
        } else {
            // do not repeat this message over and over again
            $this->add_message('noduty_file_missing','für den Monat '.$this->month_int.'/'.$this->year_int.' existieren noch keine Wünsche in '.__FUNCTION__.'!');
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
                $start_date = new \DateTime(trim($wish_limits[0]));
                $end_date = new \DateTime(trim($wish_limits[1]));
            } else { // assume only a single date entry is given, see @TODO for this
                $start_date = $end_date = new \DateTime(trim($wish_limits[0]));
            }

            if($this->isInDateRange($day, $start_date, $end_date)) {
                $this->add_message("for $candidate a noduty wish for ".$start_date->format('Y-m-d')." till ".$end_date->format('Y-m-d')." indludes actual day ".$day->format('Y-m-d'));
                return true;
            }
        }
        // if we made it here all tests have yielded no result
        return false;
    }

    function candidate_has_duty_wish($day, $people_available) {
        global $config;
        $wishes_file = './config/wishes_'.$this->month_int.'_'.$this->year_int.'.php';
        if (file_exists($wishes_file)) {
            include_once($wishes_file);
        } else {
            $this->add_message('duty_file_missing','für den Monat '.$this->month_int.'/'.$this->year_int.' existieren noch keine Wünsche in '.__FUNCTION__.'!');
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
                    $start_date = new \DateTime(trim($wish_limits[0]));
                    $end_date = new \DateTime(trim($wish_limits[1]));
                } else { // assume only a single date entry is given, see @TODO for this
                    $start_date = $end_date = new \DateTime(trim($wish_limits[0]));
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
            $candidate == $this->dienstplan[$day-1]) { // and array_key_exists($day-1, $dutyroster)
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

    function add_message($message, $level='debug') {
        $this->messages[$level][] = $message;
    }
    function full_date($target_day) {
        $fulldate = $this->year_int.'-'.$this->month_int.'-'.$target_day;
        return(new \DateTime(trim($fulldate)));
    }

    function isInDateRange(\DateTime $date, \DateTime $startDate, \DateTime $endDate) {
        return($date >= $startDate and $date <= $endDate); // true or false
    }
}
