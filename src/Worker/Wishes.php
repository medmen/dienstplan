<?php
namespace Dienstplan\Worker;
/**
 * Created by PhpStorm.
 * User: galak
 * Date: 02.05.17
 * Time: 21:25
 */
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

class Wishes{
    public $monthyear;

    function __construct($monthyear = false)
    {
        global $config;
        // load people
        require_once('./config/general.php');
        require_once('./config/people.php');

        $now = getdate();
        $this->debug = array();
        $this->target_month = sprintf('%02d', $now['mon'] + 1);
        $this->target_year = $now['mon'] == 12 ? $now['year'] + 1 : $now['year'];

        // override defaults if value is passed and is of format mm_YYYY
        if($monthyear) {
            // do cruel check
            list($target_month, $target_year) = explode('_', $monthyear);
            if( is_int($target_month)
                and (0 < $target_month and $target_month < 13)
                and is_int($target_year)
                and ($target_year < $now['year'] + 2)
                and ($target_year > $now['year'] - 2)) {
                    $this->target_month = $target_month;
                    $this->target_year = $target_year;
            }
        }

        // see how many days the target month can have
        $d = new DateTime( $this->target_year.'-'.$this->target_month);
        $this->max_days_in_target_month = $d->format('t'); // see date_format
    }

    function load_wishes() {
        $wishes_file = './config/wishes_'.$this->target_month.'_'.$this->target_year.'.php';
        if (file_exists($wishes_file)) {
            include_once($wishes_file);
        } else {
            $this->debug[] = 'no wishes file for month '.$this->target_month.'_'.$this->target_year.' was found.';
        }

    }

    function get_duty($person) {
        global $config;
        $ret = ''; // initialize string we will return
        $counter = 0;
        $first_of_month = new DateTime($this->target_year.'-'.$this->target_month.'-1');
        $last_of_month = new DateTime($this->target_year.'-'.$this->target_month.'-'.$this->max_days_in_target_month);

        if (is_array($config['wishes']['duty']) and array_key_exists($person, $config['wishes']['duty'])) {
            foreach($config['wishes']['duty'][$person] as $wid => $wish_date) {
                if(empty($wish_date)) {
                    // arrays are stored with trailing comma, so there will be empty array elements
                    continue;
                }
                $this->debug[] = "for $person wid is $wid and wishdate is ".var_export($wish_date, true);
                /**
                 * by convention a wish can be either a range of dates consisting
                 * of a start date and end date or a single date
                 **/
                // very crude check, TODO: make date storage and retrieval much safer
                $date_limits = explode('~', $wish_date);
                if(count($date_limits) == 2) {
                    $start_date = new DateTime(trim($date_limits[0]));
                    $end_date = new DateTime(trim($date_limits[1]));
                } else { // assume only a single date entry is given, see @TODO for this
                    $start_date = $end_date = new DateTime(trim($date_limits[0]));
                }

                $this->debug[] = "StartDate is ".$start_date->format('d.m.Y')." AND EndDate is ".$end_date->format('d.m.Y');
                // see if whole period is in target month, chop otherwise
                $start_in_range = $this->isInDateRange($start_date, $first_of_month, $last_of_month);
                $end_in_range = $this->isInDateRange($end_date, $first_of_month, $last_of_month);
                $this->debug[] = "StartDate is in range: $start_in_range AND ";
                $this->debug[] = "EndDate is in range: $end_in_range <br>";

                $ret.= '<input class="datepicker" type="text" size="15" name="duty_'.$person.'[]" id="duty_'.$person.'_'.$counter.'" placeholder="Dienstwunsch"';
                if($start_in_range && $end_in_range) {
                    if($start_date == $end_date) {
                        $ret.= ' value="'.$start_date->format('d.m.Y').'"';
                        $ret.= '><script id="sduty_'.$person.'_'.$counter.'"> $(function() { $("#duty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings_single); });</script>'."<br>\n";
                    } else {
                        $ret.= ' value="'.$start_date->format('d.m.Y').' ~ '.$end_date->format('d.m.Y').'"';
                        $ret.= '><script id="sduty_'.$person.'_'.$counter.'"> $(function() { $("#duty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
                    }
                }
                elseif ($start_in_range) {
                    $ret.= ' value="'.$start_date->format('d.m.Y').' ~ '.$last_of_month->format('d.m.Y').'"';
                    $ret.= '><script id="sduty_'.$person.'_'.$counter.'"> $(function() { $("#duty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
                }
                elseif ($end_in_range) {
                    $ret.= ' value="'.$first_of_month->format('d.m.Y').' ~ '.$end_date->format('d.m.Y').'"';
                    $ret.= '><script id="sduty_'.$person.'_'.$counter.'"> $(function() { $("#duty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
                }

                // Field filled, now raise the counter!
                $counter++;
            }
        } else {
            $this->debug[] = "$person is not in duty";
        }
        // no matter if duties were found, we have to return at least 1 empty field to fill out
        $ret.= '<input class="datepicker" type="text" size="15" name="duty_'.$person.'[]" id="duty_'.$person.'_'.$counter.'" placeholder="Dienstwunsch">';
        $ret.= '<script id="sduty_'.$person.'_'.$counter.'"> $(function() { $("#duty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
        return $ret;
    }

    function get_noduty($person) {
        global $config;
        $ret = ''; // initialize string we will return
        $counter = 0;
        $first_of_month = new DateTime($this->target_year.'-'.$this->target_month.'-1');
        $last_of_month = new DateTime($this->target_year.'-'.$this->target_month.'-'.$this->max_days_in_target_month);

        if (is_array($config['wishes']['noduty']) and array_key_exists($person, $config['wishes']['noduty'])) {
            foreach($config['wishes']['noduty'][$person] as $wid => $wish_date) {
                if(empty($wish_date)) {
                    // arrays are stored with trailing comma, so there will be empty array elements
                    continue;
                }
                $this->debug[] = "for $person wid is $wid and noduty-wishdate is ".var_export($wish_date, true);
                /**
                 * by convention a wish can be either a range of dates consisting
                 * of a start date and end date or a single date
                 **/
                // very crude check, TODO: make date storage and retrieval much safer
                $date_limits = explode('~', $wish_date);
                if(count($date_limits) == 2) {
                    $start_date = new DateTime(trim($date_limits[0]));
                    $end_date = new DateTime(trim($date_limits[1]));
                } else { // assume only a single date entry is given, see @TODO for this
                    $start_date = $end_date = new DateTime(trim($date_limits[0]));
                }

                $this->debug[] = "StartDate is ".$start_date->format('d.m.Y')." AND EndDate is ".$end_date->format('d.m.Y');
                // see if whole period is in target month, chop otherwise
                $start_in_range = $this->isInDateRange($start_date, $first_of_month, $last_of_month);
                $end_in_range = $this->isInDateRange($end_date, $first_of_month, $last_of_month);
                $this->debug[] = "StartDate is in range: $start_in_range AND ";
                $this->debug[] = "EndDate is in range: $end_in_range <br>";

                $ret.= '<input class="datepicker" type="text" size="15" name="noduty_'.$person.'[]" id="noduty_'.$person.'_'.$counter.'" placeholder="Dienstfrei-Wunsch"';
                if($start_in_range && $end_in_range) {
                    if($start_date == $end_date) {
                        $ret.= ' value="'.$start_date->format('d.m.Y').'"';
                        $ret.= '><script id="snoduty_'.$person.'_'.$counter.'"> $(function() { $("#noduty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings_single); });</script>'."<br>\n";
                    } else {
                        $ret.= ' value="'.$start_date->format('d.m.Y').' ~ '.$end_date->format('d.m.Y').'"';
                        $ret.= '><script id="snoduty_'.$person.'_'.$counter.'"> $(function() { $("#noduty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
                    }
                }
                elseif ($start_in_range) {
                    $ret.= ' value="'.$start_date->format('d.m.Y').' ~ '.$last_of_month->format('d.m.Y').'"';
                    $ret.= '><script id="snoduty_'.$person.'_'.$counter.'"> $(function() { $("#noduty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
                }
                elseif ($end_in_range) {
                    $ret.= ' value="'.$first_of_month->format('d.m.Y').' ~ '.$end_date->format('d.m.Y').'"';
                    $ret.= '><script id="snoduty_'.$person.'_'.$counter.'"> $(function() { $("#noduty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
                }

                // Field filled, now raise the counter!
                $counter++;
            }
        } else {
            $this->debug[] = "$person is not in noduty";
        }
        // no matter if duties were found, we have to return at least 1 empty field to fill out
        $ret.= '<input class="datepicker" type="text" size="15" name="noduty_'.$person.'[]" id="noduty_'.$person.'_'.$counter.'" placeholder="Dienstfrei-Wunsch">';
        $ret.= '<script id="snoduty_'.$person.'_'.$counter.'"> $(function() { $("#noduty_'.$person.'_'.$counter.'").dateRangePicker($.datepicker_settings); });</script>'."<br>\n";
        return $ret;
        // return '<input  class="datepicker" type="text" size="15" name="noduty_'.$person.'" id="noduty_'.$person.'_0" placeholder="Dienstfrei-Wunsch">'."<br>";
    }

    function save($wuensche_arr) {
        // remove submit button value
        unset($wuensche_arr['dpupdate']);
        // remove empty values recursively
        $wuensche_arr = $this->array_remove_empty_recursive($wuensche_arr);

        $allowed_dutytypes = array('duty', 'noduty');

        foreach($wuensche_arr as $name => $value) {
            list($dutytype, $personname) = explode('_', $name);
            // cruel check
            if(!in_array($dutytype, $allowed_dutytypes)) {
                continue;
            }

            foreach($value as $vid => $daterange) {
                ${$dutytype}[$personname][$vid] = $daterange;
            }
        }

        $file_name = 'config/wishes_'.$this->target_month.'_'.$this->target_year.'.php';
        $file_content = "<?php\nglobal \$config;\n";
        foreach ($allowed_dutytypes as $dutytype) {
            if(is_array(${$dutytype})) {
                $file_content.= '$config[\'wishes\'][\''.$dutytype.'\'] = '.var_export(${$dutytype}, true).";\n";
            }
        }

        $success = file_put_contents($file_name, $file_content, LOCK_EX);
        // $success can not simple be returned because it holds the number of bytes written, no bool
        if($success) {
            return true;
        }

    }

    // HELPER FUNCTIONS
    function getdebug() {
        global $config;
        if(true == $config['general']['debug']) {
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
        return($date >= $startDate && $date <= $endDate);
    }

    function array_remove_empty_recursive($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->array_remove_empty_recursive($haystack[$key]);
            }

            $haystack = array_filter($haystack); // remove empty values
        }

        return $haystack;
    }

    function get_people()
    {
        global $config;
        if (!is_array($config['people'])) {
            return array();
        }

        $people_available = array();
        $user_logged_in = $_SESSION['username'];

        // only Admins can change wishes for everyone
        if (isset($config['people'][$user_logged_in]['is_admin']) and
            true === $config['people'][$user_logged_in]['is_admin']) {
            foreach ($config['people'] as $i => $ppl) {
                if (is_array($ppl)) {
                    $people_available[] = $i;
                }

                if (is_string($ppl)) {
                    $people_available[] = $ppl;
                }
            }
        } else {
            # logged in user can change own wishes
            $people_available[] = $user_logged_in;
        }

        return $people_available;
    }
}

// load people
$wuensche = new wishes();
session_start();
if(!isset($_SESSION['username'])) {
    session_destroy();
    header('Location: login.php');
}

$monat = new DateTime($wuensche->target_year.'-'.$wuensche->target_month);
setlocale(LC_TIME, 'de_DE.UTF-8');
$monat_formatiert = strftime("%B %Y", $monat->getTimestamp());

if($_POST['dpupdate']) {
    $wuensche->debug[] = var_export($_POST, true);
    $success = $wuensche->save($_POST);
    if($success) {
        $message = "Wünsche wurden erfolgreich gespeichert";
    }
}

$wuensche->load_wishes();

$output = '<form id="frm_duty" method= "post" action="Wishes.php"><div class="gridcontainer">';
$output.= '<div class="box">Name</div>'."\n".'<div class="box">Dienstwunsch</div>'."\n".'<div class="box">Frei-Wunsch</div>'."\n";
foreach($wuensche->get_people() as $person) {
    $person = trim($person);
    $output.= '<div id="person_'.$person.'" class="box">'.$person.'</div>'."\n";
    $output.= '<div id="duty_'.$person.'" class="box autoinput">'.$wuensche->get_duty($person).'</div>'."\n";
    $output.= '<div id="noduty_'.$person.'" class="box autoinput">'.$wuensche->get_noduty($person).'</div>'."\n";
}
$output.= '</div><input type="submit" name="dpupdate"></form>';

$debug = $wuensche->getdebug();
?>

<html lang="de">
<head>
<?php include 'header.html';?>
    <link rel="stylesheet" href="../../public/css/daterangepicker.css">
    <script src="../../index.php"></script>
    <script src="../../public/js/jquery-3.2.1.min.js"></script>
    <script src="../../public/js/jq_new-inputfield.js"></script>
    <script>
        $.datepicker_settings =
            {
                singleMonth: true,
                showShortcuts: false,
                showTopbar: false,
                startOfWeek: 'monday',
                separator : ' ~ ',
                format: 'DD.MM.YYYY',
                startDate: '<?php echo '01.'.$wuensche->target_month.'.'.$wuensche->target_year;?>',
                endDate: '<?php echo $wuensche->max_days_in_target_month.'.'.$wuensche->target_month.'.'.$wuensche->target_year;?>'
            };

        $.datepicker_settings_single =
            {
                singleMonth: true,
                showShortcuts: false,
                showTopbar: false,
                startOfWeek: 'monday',
                singleDate : true,
                format: 'DD.MM.YYYY',
                startDate: '<?php echo '01.'.$wuensche->target_month.'.'.$wuensche->target_year;?>',
                endDate: '<?php echo $wuensche->max_days_in_target_month.'.'.$wuensche->target_month.'.'.$wuensche->target_year;?>'
            };


    </script>
    <script src="../../public/js/jquery.daterangepicker.js"></script>
</head>
<body>
<div class="container">
    <?php include 'navigation.php';?>
    <h1>WÜNSCHE für <?php echo $monat_formatiert; ?></h1>
    <?php if(is_array($debug)): ?>
    <section class="row">
        <aside><?php implode("<br>\n", $debug); ?></aside>
    </section>
    <?php endif; ?>

    <section class="row">
        <aside><?php echo $message; ?></aside>
    </section>

    <section class="row">
        <article id="main" class="nine columns">
            <?php   echo $output; ?>
        </article>
<!--
        <div class="two columns">
            <aside>
                <pre>here be dragons</pre>
            </aside>
            <aside id="debug">
                <pre>here too</pre>
            </aside>
        </div>
-->
    </section>
</div>
</body>
</html>

